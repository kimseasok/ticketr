<?php

namespace App\Modules\Helpdesk\Services;

use App\Modules\Helpdesk\Models\Ticket;
use App\Modules\Helpdesk\Models\TicketMessage;
use App\Modules\Helpdesk\Models\TicketParticipant;
use App\Modules\Helpdesk\Services\AttachmentScanner;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TicketMessageService
{
    public function __construct(
        private readonly DatabaseManager $database,
        private readonly AttachmentScanner $scanner
    ) {
    }

    public function append(Ticket $ticket, array $payload): TicketMessage
    {
        return $this->database->transaction(function () use ($ticket, $payload) {
            $externalId = $payload['external_id'] ?? null;
            $dedupeHash = TicketMessage::generateHash(
                $externalId,
                (string) ($payload['author_id'] ?? ''),
                Str::of($payload['body'] ?? '')->limit(140)
            );

            $existing = TicketMessage::query()
                ->where('tenant_id', $ticket->tenant_id)
                ->where(function ($query) use ($externalId, $dedupeHash) {
                    $query->where('dedupe_hash', $dedupeHash);

                    if ($externalId !== null) {
                        $query->orWhere('external_id', $externalId);
                    }
                })
                ->first();

            if ($existing) {
                return $existing;
            }

            $visibility = $this->resolveVisibility($payload);

            $metadata = $payload['metadata'] ?? [];

            if (! empty($payload['email'])) {
                $metadata['email'] = array_merge($metadata['email'] ?? [], $payload['email']);
            }

            $attributes = [
                'tenant_id' => $ticket->tenant_id,
                'brand_id' => $ticket->brand_id,
                'author_type' => $payload['author_type'] ?? 'system',
                'author_id' => $payload['author_id'] ?? null,
                'visibility' => $visibility,
                'channel' => $payload['channel'] ?? $ticket->channel,
                'external_id' => $externalId,
                'dedupe_hash' => $dedupeHash,
                'body' => $payload['body'] ?? '',
                'metadata' => $metadata,
                'posted_at' => $payload['posted_at'] ?? now(),
            ];

            $message = $ticket->messages()->create($attributes);

            if (! empty($payload['attachments'])) {
                $this->createAttachments($message, $payload['attachments']);
            }

            $participants = $this->filterParticipantsForVisibility($visibility, $payload['participants'] ?? []);
            $this->syncParticipants($ticket, $message, $participants, $visibility);

            $this->updateTicketTimestamps($ticket, $message);

            Log::channel('stack')->info('ticket_message.created', [
                'tenant_id' => $ticket->tenant_id,
                'brand_id' => $ticket->brand_id,
                'ticket_id' => $ticket->id,
                'message_id' => $message->id,
                'visibility' => $message->visibility,
                'channel' => $message->channel,
                'posted_at' => $message->posted_at?->toIso8601String(),
            ]);

            return $message->fresh(['attachments']);
        });
    }

    private function resolveVisibility(array &$payload): string
    {
        $visibility = $payload['visibility'] ?? 'public';
        $authorType = $payload['author_type'] ?? 'system';

        if ($authorType === 'contact' && $visibility !== 'public') {
            Log::channel('stack')->warning('ticket_message.visibility_downgraded', [
                'reason' => 'contact_author',
            ]);

            $visibility = 'public';
        }

        if (! in_array($visibility, ['public', 'internal'], true)) {
            $visibility = 'public';
        }

        $payload['visibility'] = $visibility;

        return $visibility;
    }

    private function createAttachments(TicketMessage $message, array $attachments): void
    {
        foreach ($attachments as $attachment) {
            $payload = array_merge([
                'tenant_id' => $message->tenant_id,
                'brand_id' => $message->brand_id,
            ], Arr::only($attachment, [
                'disk',
                'path',
                'filename',
                'mime_type',
                'size',
                'metadata',
            ]));

            $record = $message->attachments()->create($payload);

            $this->scanner->scan($record);
        }

        $message->update(['attachments_count' => $message->attachments()->count()]);
    }

    private function filterParticipantsForVisibility(string $visibility, array $participants): array
    {
        if ($visibility !== 'internal') {
            return $participants;
        }

        return array_values(array_filter($participants, function (array $participant): bool {
            $type = $participant['participant_type'] ?? 'contact';

            return $type === 'user';
        }));
    }

    private function syncParticipants(Ticket $ticket, TicketMessage $message, array $participants, string $visibility): void
    {
        foreach ($participants as $participant) {
            $record = TicketParticipant::query()->firstOrNew([
                'tenant_id' => $ticket->tenant_id,
                'ticket_id' => $ticket->id,
                'participant_type' => $participant['participant_type'] ?? 'contact',
                'participant_id' => $participant['participant_id'] ?? null,
            ]);

            $record->brand_id = $ticket->brand_id;
            $record->last_message_id = $message->id;
            $record->applySnapshot($this->sanitizeParticipantSnapshot($participant, $visibility));
            $record->save();
        }
    }

    private function sanitizeParticipantSnapshot(array $participant, string $messageVisibility): array
    {
        $type = $participant['participant_type'] ?? 'contact';
        $role = $participant['role'] ?? ($type === 'user' ? 'agent' : 'requester');
        $visibility = $participant['visibility'] ?? ($type === 'user' ? 'internal' : 'external');

        if ($type !== 'user') {
            $visibility = 'external';
        }

        if ($messageVisibility === 'internal' && $type !== 'user') {
            $visibility = 'external';
        }

        return array_merge($participant, [
            'role' => $role,
            'visibility' => $visibility,
        ]);
    }

    private function updateTicketTimestamps(Ticket $ticket, TicketMessage $message): void
    {
        $attributes = ['last_activity_at' => now()];

        if ($message->author_type === 'contact') {
            $attributes['last_customer_reply_at'] = now();
        }

        if ($message->author_type === 'user') {
            $attributes['last_agent_reply_at'] = now();
        }

        $ticket->forceFill($attributes)->save();
    }
}
