<?php

namespace App\Modules\Helpdesk\Services\Email;

use App\Modules\Helpdesk\Models\Attachment;
use App\Modules\Helpdesk\Models\EmailAttachment;
use App\Modules\Helpdesk\Models\EmailMailbox;
use App\Modules\Helpdesk\Models\EmailOutboundMessage;
use App\Modules\Helpdesk\Models\TicketMessage;
use App\Modules\Helpdesk\Services\Email\Data\DeliveryResult;
use App\Modules\Helpdesk\Services\Email\Connectors\SmtpMailboxDeliverer;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class EmailDeliveryService
{
    public function __construct(
        private readonly DatabaseManager $database,
        private readonly MailboxConnectorRegistry $registry
    ) {
    }

    public function queueFromTicketMessage(TicketMessage $message): ?EmailOutboundMessage
    {
        $emailMeta = Arr::get($message->metadata ?? [], 'email', []);
        $recipients = $this->normalizeRecipients($emailMeta);

        if (empty($recipients['to'])) {
            return null;
        }

        $mailbox = $this->resolveMailbox($message, $emailMeta);

        $message->loadMissing('ticket');

        return $this->database->transaction(function () use ($message, $recipients, $mailbox, $emailMeta) {
            $outbound = EmailOutboundMessage::create([
                'tenant_id' => $message->tenant_id,
                'brand_id' => $message->brand_id,
                'mailbox_id' => $mailbox->id,
                'ticket_id' => $message->ticket_id,
                'ticket_message_id' => $message->id,
                'subject' => Arr::get($emailMeta, 'subject', optional($message->ticket)->subject),
                'to_recipients' => $recipients['to'],
                'cc_recipients' => $recipients['cc'],
                'bcc_recipients' => $recipients['bcc'],
                'text_body' => $emailMeta['text_body'] ?? strip_tags($message->body),
                'html_body' => $emailMeta['html_body'] ?? null,
                'status' => 'queued',
                'scheduled_at' => now(),
                'metadata' => [
                    'trace_id' => Str::uuid()->toString(),
                ],
            ]);

            $this->syncOutboundAttachments($outbound, $message);

            Log::channel('stack')->info('email.outbound.queued', [
                'mailbox_id' => $mailbox->id,
                'tenant_id' => $message->tenant_id,
                'outbound_id' => $outbound->id,
                'ticket_id' => $message->ticket_id,
            ]);

            return $outbound;
        });
    }

    public function deliver(EmailOutboundMessage $outbound): DeliveryResult
    {
        $mailbox = $outbound->mailbox ?? EmailMailbox::find($outbound->mailbox_id);

        if (! $mailbox instanceof EmailMailbox) {
            throw new \RuntimeException('Outbound mailbox configuration missing.');
        }

        $deliverer = $this->registry->resolveDeliverer($mailbox->protocol, $mailbox) ?? new SmtpMailboxDeliverer($mailbox);

        try {
            $outbound->forceFill([
                'status' => 'sending',
                'attempts' => $outbound->attempts + 1,
                'last_attempted_at' => now(),
            ])->save();

            $result = $deliverer->deliver($outbound);

            if ($result->success) {
                $outbound->forceFill([
                    'status' => 'sent',
                    'sent_at' => now(),
                    'provider_message_id' => $result->providerMessageId,
                    'last_error' => null,
                ])->save();
            } else {
                $outbound->forceFill([
                    'status' => 'failed',
                    'last_error' => $result->error,
                ])->save();
            }

            Log::channel('stack')->info('email.outbound.delivered', [
                'mailbox_id' => $mailbox->id,
                'outbound_id' => $outbound->id,
                'tenant_id' => $outbound->tenant_id,
                'status' => $outbound->status,
            ]);

            return $result;
        } catch (Throwable $exception) {
            $outbound->forceFill([
                'status' => 'failed',
                'last_error' => [
                    'message' => $exception->getMessage(),
                ],
            ])->save();

            Log::channel('stack')->error('email.outbound.failed', [
                'mailbox_id' => $mailbox->id,
                'outbound_id' => $outbound->id,
                'tenant_id' => $outbound->tenant_id,
                'error' => $exception->getMessage(),
            ]);

            return new DeliveryResult(false, error: ['message' => $exception->getMessage()]);
        }
    }

    private function resolveMailbox(TicketMessage $message, array $emailMeta): EmailMailbox
    {
        $mailboxId = Arr::get($emailMeta, 'mailbox_id');

        $query = EmailMailbox::query()
            ->where('tenant_id', $message->tenant_id)
            ->active()
            ->outbound()
            ->forBrand($message->brand_id)
            ->orderByRaw("CASE WHEN direction = 'bidirectional' THEN 0 WHEN direction = 'outbound' THEN 1 ELSE 2 END");

        if ($mailboxId) {
            $mailbox = (clone $query)->where('id', $mailboxId)->first();

            if ($mailbox) {
                return $mailbox;
            }
        }

        $mailbox = $query->first();

        if (! $mailbox) {
            throw new \RuntimeException('No active outbound mailbox configured for tenant.');
        }

        return $mailbox;
    }

    private function normalizeRecipients(array $emailMeta): array
    {
        $to = array_values(array_filter(array_map('trim', Arr::wrap(Arr::get($emailMeta, 'to', []))), fn ($value) => filter_var($value, FILTER_VALIDATE_EMAIL)));
        $cc = array_values(array_filter(array_map('trim', Arr::wrap(Arr::get($emailMeta, 'cc', []))), fn ($value) => filter_var($value, FILTER_VALIDATE_EMAIL)));
        $bcc = array_values(array_filter(array_map('trim', Arr::wrap(Arr::get($emailMeta, 'bcc', []))), fn ($value) => filter_var($value, FILTER_VALIDATE_EMAIL)));

        return [
            'to' => array_unique($to),
            'cc' => array_unique($cc),
            'bcc' => array_unique($bcc),
        ];
    }

    private function syncOutboundAttachments(EmailOutboundMessage $outbound, TicketMessage $message): void
    {
        $message->loadMissing('attachments');

        foreach ($message->attachments as $attachment) {
            if (! $attachment instanceof Attachment) {
                continue;
            }

            EmailAttachment::updateOrCreate([
                'tenant_id' => $outbound->tenant_id,
                'message_type' => EmailOutboundMessage::class,
                'message_id' => $outbound->id,
                'attachment_id' => $attachment->id,
            ], [
                'brand_id' => $outbound->brand_id,
                'mailbox_id' => $outbound->mailbox_id,
                'filename' => $attachment->filename,
                'mime_type' => $attachment->mime_type,
                'size' => $attachment->size,
                'checksum' => hash('sha256', sprintf('%s:%s', $attachment->disk, $attachment->path)),
                'metadata' => [
                    'disk' => $attachment->disk,
                    'path' => $attachment->path,
                ],
            ]);
        }
    }
}
