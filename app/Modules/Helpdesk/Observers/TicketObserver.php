<?php

namespace App\Modules\Helpdesk\Observers;

use App\Modules\Helpdesk\Models\AuditLog;
use App\Modules\Helpdesk\Models\Ticket;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class TicketObserver
{
    public function created(Ticket $ticket): void
    {
        $this->recordAudit($ticket, 'created', [], $ticket->auditPayload());
    }

    public function updated(Ticket $ticket): void
    {
        $changes = Arr::only($ticket->getChanges(), array_keys($ticket->auditPayload()));
        if ($changes === []) {
            return;
        }

        $this->recordAudit(
            $ticket,
            'updated',
            Arr::only($ticket->getOriginal(), array_keys($changes)),
            Arr::only($ticket->getAttributes(), array_keys($changes))
        );
    }

    public function deleted(Ticket $ticket): void
    {
        $this->recordAudit($ticket, 'deleted', $ticket->auditPayload(), []);
    }

    private function recordAudit(Ticket $ticket, string $action, array $oldValues, array $newValues): void
    {
        $payload = [
            'tenant_id' => $ticket->tenant_id,
            'brand_id' => $ticket->brand_id,
            'user_id' => auth()->id(),
            'action' => "ticket.{$action}",
            'auditable_type' => Ticket::class,
            'auditable_id' => $ticket->id,
            'old_values' => $oldValues ?: null,
            'new_values' => $newValues ?: null,
            'metadata' => [
                'reference' => $ticket->reference,
            ],
        ];

        AuditLog::create($payload);

        Log::info('ticket.audit', [
            'action' => $action,
            'ticket_id' => $ticket->id,
            'tenant_id' => $ticket->tenant_id,
            'brand_id' => $ticket->brand_id,
            'user_id' => $payload['user_id'],
        ]);
    }
}
