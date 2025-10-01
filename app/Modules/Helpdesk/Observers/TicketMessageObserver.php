<?php

namespace App\Modules\Helpdesk\Observers;

use App\Modules\Helpdesk\Models\AuditLog;
use App\Modules\Helpdesk\Models\TicketMessage;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class TicketMessageObserver
{
    public function created(TicketMessage $message): void
    {
        $this->recordAudit($message, 'created', [], $message->auditPayload());
    }

    public function updated(TicketMessage $message): void
    {
        $original = $message->getOriginal();
        $changes = Arr::only($message->getChanges(), ['visibility', 'channel', 'attachments_count', 'metadata']);

        if ($changes === []) {
            return;
        }

        $this->recordAudit(
            $message,
            'updated',
            $this->sanitize(Arr::only($original, array_keys($changes))),
            $this->sanitize(Arr::only($message->getAttributes(), array_keys($changes)))
        );
    }

    public function deleted(TicketMessage $message): void
    {
        $this->recordAudit($message, 'deleted', $message->auditPayload(), []);
    }

    private function recordAudit(TicketMessage $message, string $action, array $oldValues, array $newValues): void
    {
        $payload = [
            'tenant_id' => $message->tenant_id,
            'brand_id' => $message->brand_id,
            'user_id' => auth()->id(),
            'action' => "ticket_message.{$action}",
            'auditable_type' => TicketMessage::class,
            'auditable_id' => $message->id,
            'old_values' => $oldValues ?: null,
            'new_values' => $newValues ?: null,
            'metadata' => [
                'ticket_id' => $message->ticket_id,
            ],
        ];

        AuditLog::create($payload);

        Log::channel('stack')->info('ticket_message.audit', [
            'action' => $action,
            'message_id' => $message->id,
            'ticket_id' => $message->ticket_id,
            'tenant_id' => $message->tenant_id,
        ]);
    }

    private function sanitize(array $values): array
    {
        if (array_key_exists('metadata', $values)) {
            $raw = $values['metadata'];

            if (is_string($raw)) {
                $raw = json_decode($raw, true) ?: [];
            }

            $values['metadata'] = Arr::only($raw ?? [], ['source', 'adapter']);
        }

        return $values;
    }
}
