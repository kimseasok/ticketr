<?php

namespace App\Modules\Helpdesk\Services\Email;

use App\Modules\Helpdesk\Models\Contact;
use App\Modules\Helpdesk\Models\EmailAttachment;
use App\Modules\Helpdesk\Models\EmailInboundMessage;
use App\Modules\Helpdesk\Models\EmailMailbox;
use App\Modules\Helpdesk\Models\Ticket;
use App\Modules\Helpdesk\Models\TicketMessage;
use App\Modules\Helpdesk\Services\AttachmentScanner;
use App\Modules\Helpdesk\Services\Email\Contracts\MailboxFetcher;
use App\Modules\Helpdesk\Services\Email\Data\InboundEmailMessage;
use App\Modules\Helpdesk\Services\TicketMessageService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class EmailIngestionService
{
    public function __construct(
        private readonly DatabaseManager $database,
        private readonly TicketMessageService $ticketMessages,
        private readonly MailboxConnectorRegistry $registry,
        private readonly AttachmentScanner $scanner
    ) {
    }

    /**
     * Fetch new messages from the configured connector and persist them for processing.
     *
     * @return EmailInboundMessage[]
     */
    public function synchronizeMailbox(EmailMailbox $mailbox): array
    {
        if (! $mailbox->supportsInbound()) {
            return [];
        }

        $fetcher = $this->registry->resolveFetcher($mailbox->protocol, $mailbox);

        if (! $fetcher instanceof MailboxFetcher) {
            Log::channel('stack')->warning('email.mailbox.fetcher_missing', [
                'mailbox_id' => $mailbox->id,
                'protocol' => $mailbox->protocol,
            ]);

            return [];
        }

        $messages = [];

        try {
            foreach ($fetcher->fetch($mailbox) as $rawMessage) {
                $messages[] = $this->storeRawMessage($mailbox, $rawMessage);
            }

            $mailbox->forceFill([
                'last_synced_at' => now(),
                'sync_state' => array_merge($mailbox->sync_state ?? [], [
                    'last_fetch_at' => now()->toIso8601String(),
                ]),
            ])->save();
        } catch (Throwable $exception) {
            Log::channel('stack')->error('email.mailbox.sync_failed', [
                'mailbox_id' => $mailbox->id,
                'protocol' => $mailbox->protocol,
                'tenant_id' => $mailbox->tenant_id,
                'error' => $exception->getMessage(),
            ]);
        }

        return $messages;
    }

    public function storeRawMessage(EmailMailbox $mailbox, InboundEmailMessage $raw): EmailInboundMessage
    {
        return $this->database->transaction(function () use ($mailbox, $raw) {
            $existing = EmailInboundMessage::query()
                ->where('tenant_id', $mailbox->tenant_id)
                ->where('message_id', $raw->messageId)
                ->first();

            if ($existing) {
                return $existing;
            }

            $record = EmailInboundMessage::create([
                'tenant_id' => $mailbox->tenant_id,
                'brand_id' => $mailbox->brand_id,
                'mailbox_id' => $mailbox->id,
                'message_id' => $raw->messageId,
                'thread_id' => $raw->threadId,
                'subject' => $raw->subject,
                'from_name' => $raw->fromName,
                'from_email' => $raw->fromEmail,
                'to_recipients' => $raw->to,
                'cc_recipients' => $raw->cc,
                'bcc_recipients' => $raw->bcc,
                'text_body' => $raw->textBody,
                'html_body' => $raw->htmlBody,
                'attachments_count' => count($raw->attachments),
                'status' => 'pending',
                'received_at' => $raw->receivedAt?->toDateTimeString() ?? now(),
                'headers' => $this->sanitizeHeaders($raw->headers),
            ]);

            foreach ($raw->attachments as $attachment) {
                $this->storeAttachment($record, $attachment);
            }

            Log::channel('stack')->info('email.inbound.stored', [
                'mailbox_id' => $mailbox->id,
                'tenant_id' => $mailbox->tenant_id,
                'message_hash' => hash('sha256', $raw->messageId),
            ]);

            return $record;
        });
    }

    /**
     * Process pending inbound messages for the given mailbox.
     *
     * @return EmailInboundMessage[]
     */
    public function processMailbox(EmailMailbox $mailbox): array
    {
        $messages = EmailInboundMessage::query()
            ->where('tenant_id', $mailbox->tenant_id)
            ->where('mailbox_id', $mailbox->id)
            ->pending()
            ->orderBy('received_at')
            ->limit(50)
            ->get();

        return $messages->map(function (EmailInboundMessage $message) use ($mailbox) {
            return $this->processMessage($message, $mailbox);
        })->all();
    }

    public function processMessage(EmailInboundMessage $message, ?EmailMailbox $mailbox = null): EmailInboundMessage
    {
        $mailbox ??= $message->mailbox;

        if (! $mailbox instanceof EmailMailbox) {
            throw new \RuntimeException('Mailbox required to process inbound message.');
        }

        try {
            return $this->database->transaction(function () use ($mailbox, $message) {
                $contact = $this->resolveContact($mailbox, $message);
                $ticket = $this->resolveTicket($mailbox, $message, $contact);

                $attachmentsPayload = $this->prepareAttachmentsForTicket($message);

                $ticketMessage = $this->ticketMessages->append($ticket, [
                    'author_type' => 'contact',
                    'author_id' => $contact?->id,
                    'channel' => 'email',
                    'external_id' => $message->message_id,
                    'body' => $message->html_body ?? $message->text_body ?? '',
                    'metadata' => $this->buildMetadata($mailbox, $message, $contact),
                    'posted_at' => $message->received_at ?? now(),
                    'attachments' => $attachmentsPayload,
                ]);

                $this->linkAttachments($message, $ticketMessage);

                $message->forceFill([
                    'ticket_id' => $ticket->id,
                    'ticket_message_id' => $ticketMessage->id,
                    'status' => 'processed',
                    'processed_at' => now(),
                    'attachments_count' => $ticketMessage->attachments()->count(),
                    'error_info' => null,
                ])->save();

                Log::channel('stack')->info('email.inbound.processed', [
                    'mailbox_id' => $mailbox->id,
                    'tenant_id' => $mailbox->tenant_id,
                    'ticket_id' => $ticket->id,
                    'message_id' => $ticketMessage->id,
                ]);

                return $message;
            });
        } catch (Throwable $exception) {
            $message->forceFill([
                'status' => 'failed',
                'error_info' => [
                    'message' => $exception->getMessage(),
                    'trace_id' => Str::uuid()->toString(),
                ],
            ])->save();

            Log::channel('stack')->error('email.inbound.failed', [
                'mailbox_id' => $mailbox->id,
                'tenant_id' => $mailbox->tenant_id,
                'message_hash' => hash('sha256', $message->message_id),
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    private function resolveContact(EmailMailbox $mailbox, EmailInboundMessage $message): ?Contact
    {
        if (! filter_var($message->from_email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        $query = Contact::query()
            ->where('tenant_id', $mailbox->tenant_id)
            ->whereRaw('LOWER(email) = ?', [strtolower($message->from_email)]);

        if ($mailbox->brand_id) {
            $query->where(function (Builder $builder) use ($mailbox) {
                $builder->whereNull('brand_id')
                    ->orWhere('brand_id', $mailbox->brand_id);
            });
        }

        $contact = $query->first();

        if ($contact) {
            return $contact;
        }

        return Contact::create([
            'tenant_id' => $mailbox->tenant_id,
            'brand_id' => $mailbox->brand_id,
            'name' => $message->from_name ?? Str::before($message->from_email, '@'),
            'email' => $message->from_email,
            'metadata' => [
                'source' => 'email',
                'mailbox_id' => $mailbox->id,
            ],
        ]);
    }

    private function resolveTicket(EmailMailbox $mailbox, EmailInboundMessage $message, ?Contact $contact): Ticket
    {
        $ticket = $this->findTicketByThread($mailbox, $message)
            ?? $this->findTicketByReference($mailbox, $message)
            ?? $this->findLatestTicketForContact($mailbox, $contact);

        if ($ticket) {
            return $ticket;
        }

        $subject = $message->subject ?: 'New email conversation';
        $reference = sprintf('EM-%s', Str::upper(Str::random(10)));

        return Ticket::create([
            'tenant_id' => $mailbox->tenant_id,
            'brand_id' => $mailbox->brand_id,
            'contact_id' => $contact?->id,
            'subject' => Str::limit($subject, 240, ''),
            'description' => Str::limit($message->text_body ?? strip_tags((string) $message->html_body), 500),
            'status' => Ticket::STATUS_OPEN,
            'priority' => 'normal',
            'channel' => 'email',
            'reference' => $reference,
            'metadata' => [
                'source' => 'email',
                'mailbox_id' => $mailbox->id,
                'thread_id' => $message->thread_id,
            ],
        ]);
    }

    private function findTicketByThread(EmailMailbox $mailbox, EmailInboundMessage $message): ?Ticket
    {
        if (! $message->thread_id) {
            return null;
        }

        $ticketId = EmailInboundMessage::query()
            ->where('tenant_id', $mailbox->tenant_id)
            ->where('thread_id', $message->thread_id)
            ->whereNotNull('ticket_id')
            ->value('ticket_id');

        if (! $ticketId) {
            $ticketId = TicketMessage::query()
                ->where('tenant_id', $mailbox->tenant_id)
                ->whereJsonContains('metadata->email->thread_id', $message->thread_id)
                ->value('ticket_id');
        }

        return $ticketId ? Ticket::find($ticketId) : null;
    }

    private function findTicketByReference(EmailMailbox $mailbox, EmailInboundMessage $message): ?Ticket
    {
        if (! $message->subject) {
            return null;
        }

        if (! preg_match('/\[(?<ref>[A-Z]{1,5}-[A-Z0-9]{4,})\]/', $message->subject, $matches)) {
            return null;
        }

        $reference = Arr::get($matches, 'ref');

        if (! $reference) {
            return null;
        }

        return Ticket::query()
            ->where('tenant_id', $mailbox->tenant_id)
            ->where('reference', $reference)
            ->first();
    }

    private function findLatestTicketForContact(EmailMailbox $mailbox, ?Contact $contact): ?Ticket
    {
        if (! $contact) {
            return null;
        }

        return $contact->tickets()
            ->where('tenant_id', $mailbox->tenant_id)
            ->where(function (Builder $builder) {
                $builder->whereNull('archived_at')
                    ->orWhereDate('archived_at', '>=', now()->subDays(30));
            })
            ->latest('last_activity_at')
            ->first();
    }

    private function storeAttachment(EmailInboundMessage $message, array $attachment): void
    {
        $content = $attachment['content'] ?? '';
        $encoding = strtolower((string) ($attachment['encoding'] ?? ''));

        if ($encoding === 'base64') {
            $decoded = base64_decode($content, true);
            if ($decoded !== false) {
                $content = $decoded;
            }
        }

        $disk = config('filesystems.default', 'local');
        $path = sprintf('email/inbound/%s/%s', $message->mailbox_id, Str::uuid()->toString());
        Storage::disk($disk)->put($path, (string) $content);

        EmailAttachment::create([
            'tenant_id' => $message->tenant_id,
            'brand_id' => $message->brand_id,
            'mailbox_id' => $message->mailbox_id,
            'message_type' => EmailInboundMessage::class,
            'message_id' => $message->id,
            'filename' => $attachment['filename'] ?? 'attachment.bin',
            'mime_type' => $attachment['mime_type'] ?? null,
            'size' => strlen((string) $content),
            'content_id' => $attachment['content_id'] ?? null,
            'disposition' => $attachment['disposition'] ?? null,
            'checksum' => hash('sha256', (string) $content),
            'metadata' => [
                'disk' => $disk,
                'path' => $path,
            ],
        ]);
    }

    private function prepareAttachmentsForTicket(EmailInboundMessage $message): array
    {
        return $message->attachments->map(function (EmailAttachment $attachment): array {
            $metadata = $attachment->metadata ?? [];

            return [
                'disk' => Arr::get($metadata, 'disk', config('filesystems.default', 'local')),
                'path' => Arr::get($metadata, 'path'),
                'filename' => $attachment->filename,
                'mime_type' => $attachment->mime_type,
                'size' => $attachment->size,
                'metadata' => [
                    'source' => 'email',
                    'email_attachment_id' => $attachment->id,
                ],
            ];
        })->all();
    }

    private function linkAttachments(EmailInboundMessage $inbound, TicketMessage $message): void
    {
        $emailAttachments = $inbound->attachments()->get();
        $ticketAttachments = $message->attachments()->get();

        foreach ($emailAttachments as $emailAttachment) {
            $match = $ticketAttachments->first(function ($ticketAttachment) use ($emailAttachment) {
                $metadata = $ticketAttachment->metadata ?? [];

                return Arr::get($metadata, 'email_attachment_id') === $emailAttachment->id;
            });

            if ($match) {
                $emailAttachment->forceFill([
                    'attachment_id' => $match->id,
                ])->save();

                $this->scanner->scan($match);
            }
        }
    }

    private function buildMetadata(EmailMailbox $mailbox, EmailInboundMessage $message, ?Contact $contact): array
    {
        $emailMetadata = [
            'mailbox_id' => $mailbox->id,
            'thread_id' => $message->thread_id,
            'subject' => $message->subject,
            'from' => $this->hashEmail($message->from_email),
            'to' => array_map(fn (string $recipient) => $this->hashEmail($recipient), $message->to_recipients ?? []),
            'cc' => array_map(fn (string $recipient) => $this->hashEmail($recipient), $message->cc_recipients ?? []),
            'received_at' => optional($message->received_at)->toIso8601String(),
        ];

        if ($contact) {
            $emailMetadata['contact_id'] = $contact->id;
        }

        return ['email' => $emailMetadata];
    }

    private function hashEmail(?string $email): ?string
    {
        if (! $email) {
            return null;
        }

        return substr(hash('sha256', strtolower($email)), 0, 16);
    }

    private function sanitizeHeaders(array $headers): array
    {
        $allowed = ['message-id', 'in-reply-to', 'references'];

        return Arr::only(array_change_key_case($headers, CASE_LOWER), $allowed);
    }
}
