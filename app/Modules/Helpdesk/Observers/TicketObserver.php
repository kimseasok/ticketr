<?php

namespace App\Modules\Helpdesk\Observers;

use App\Jobs\ProcessTicketSla;
use App\Modules\Helpdesk\Models\AuditLog;
use App\Modules\Helpdesk\Models\Ticket;
use App\Modules\Helpdesk\Services\AutomationRuleEngine;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class TicketObserver
{
    public function created(Ticket $ticket): void
    {
        $this->recordAudit($ticket, 'created', [], $ticket->auditPayload());

        app(AutomationRuleEngine::class)->evaluate($ticket, 'ticket.created');

        ProcessTicketSla::dispatch($ticket->id);
    }

    public function updated(Ticket $ticket): void
    {
        $changes = Arr::only($ticket->getChanges(), array_keys($ticket->auditPayload()));

        if ($changes !== []) {
            $this->recordAudit(
                $ticket,
                'updated',
                $this->sanitizeValues(Arr::only($ticket->getOriginal(), array_keys($changes))),
                $this->sanitizeValues(Arr::only($ticket->getAttributes(), array_keys($changes)))
            );
        }

        $engine = app(AutomationRuleEngine::class);
        if (! $engine->isProcessing($ticket->id)) {
            $engine->evaluate($ticket, 'ticket.updated');
        }

        $slaFields = ['sla_policy_id', 'first_response_due_at', 'resolution_due_at', 'sla_snapshot'];
        if (array_intersect($slaFields, array_keys($ticket->getChanges())) !== []) {
            ProcessTicketSla::dispatch($ticket->id);
        }
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

    private function sanitizeValues(array $values): array
    {
        if (array_key_exists('metadata', $values)) {
            $raw = $values['metadata'];

            if (is_string($raw)) {
                $raw = json_decode($raw, true) ?: [];
            }

            $values['metadata'] = Arr::only($raw ?? [], ['sla']);
        }

        return $values;
    }
}
