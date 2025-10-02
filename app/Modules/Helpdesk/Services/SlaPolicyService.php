<?php

namespace App\Modules\Helpdesk\Services;

use App\Modules\Helpdesk\Models\SlaPolicy;
use App\Modules\Helpdesk\Models\Ticket;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class SlaPolicyService
{
    public function __construct(private readonly TicketSlaEvaluator $evaluator)
    {
    }

    public function assignPolicy(Ticket $ticket, ?SlaPolicy $policy = null): ?SlaPolicy
    {
        $policy ??= $this->resolvePolicyForTicket($ticket);

        if ($policy === null) {
            $ticket->sla_policy_id = null;
            $ticket->next_sla_check_at = null;
            $this->applySnapshot($ticket);

            return null;
        }

        $ticket->sla_policy_id = $policy->id;
        $this->applyTargets($ticket, $policy);
        $this->applySnapshot($ticket);

        Log::channel('structured')->info('sla.policy.assigned', [
            'ticket_id' => $ticket->id,
            'policy_id' => $policy->id,
            'tenant_id' => $ticket->tenant_id,
        ]);

        return $policy;
    }

    public function refreshTicket(Ticket $ticket): void
    {
        $policy = $ticket->slaPolicy;
        if ($policy) {
            $this->applyTargets($ticket, $policy);
        }

        $this->applySnapshot($ticket);
    }

    private function applyTargets(Ticket $ticket, SlaPolicy $policy): void
    {
        $base = $ticket->created_at ?? now();

        $ticket->first_response_due_at = Carbon::parse($base)->addMinutes($policy->first_response_minutes);
        $ticket->resolution_due_at = Carbon::parse($base)->addMinutes($policy->resolution_minutes);

        $next = collect([
            $ticket->first_response_due_at,
            $ticket->resolution_due_at,
        ])->filter()->min();

        if ($next) {
            $ticket->next_sla_check_at = Carbon::parse($next)->subMinutes($policy->grace_minutes)->max(now());
        }
    }

    private function applySnapshot(Ticket $ticket): void
    {
        $snapshot = $this->evaluator->evaluate($ticket);
        $metadata = $ticket->metadata ?? [];
        $metadata['sla'] = $snapshot;

        $ticket->sla_snapshot = $snapshot;
        $ticket->metadata = $metadata;
    }

    private function resolvePolicyForTicket(Ticket $ticket): ?SlaPolicy
    {
        return SlaPolicy::query()
            ->where('tenant_id', $ticket->tenant_id)
            ->active()
            ->where(function ($query) use ($ticket): void {
                $query->whereNull('brand_id');
                if ($ticket->brand_id !== null) {
                    $query->orWhere('brand_id', $ticket->brand_id);
                }
            })
            ->orderByDesc('brand_id')
            ->get()
            ->first(function (SlaPolicy $policy) use ($ticket): bool {
                $priorityMatch = $policy->priority_scope === null || $policy->priority_scope === $ticket->priority;
                $channelMatch = $policy->channel_scope === null || $policy->channel_scope === $ticket->channel;

                return $priorityMatch && $channelMatch;
            });
    }
}
