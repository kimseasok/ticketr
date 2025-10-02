<?php

namespace App\Jobs;

use App\Modules\Helpdesk\Models\SlaTransition;
use App\Modules\Helpdesk\Models\Ticket;
use App\Modules\Helpdesk\Services\SlaPolicyService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class ProcessTicketSla implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public int $ticketId)
    {
    }

    public function handle(SlaPolicyService $slaPolicyService): void
    {
        $ticket = Ticket::query()->with('slaPolicy')->find($this->ticketId);
        if (! $ticket) {
            return;
        }

        $previous = $ticket->sla_snapshot ?? [];

        $slaPolicyService->refreshTicket($ticket);
        $ticket->save();

        $current = $ticket->sla_snapshot ?? [];

        foreach (['first_response', 'resolution'] as $metric) {
            $before = Arr::get($previous, "{$metric}.state", 'pending');
            $after = Arr::get($current, "{$metric}.state", 'pending');

            if ($before === $after) {
                continue;
            }

            SlaTransition::create([
                'tenant_id' => $ticket->tenant_id,
                'ticket_id' => $ticket->id,
                'sla_policy_id' => $ticket->sla_policy_id,
                'metric' => $metric,
                'from_state' => $before,
                'to_state' => $after,
                'transitioned_at' => now(),
                'context' => [
                    'due_at' => Arr::get($current, "{$metric}.due_at"),
                    'completed_at' => Arr::get($current, "{$metric}.completed_at"),
                ],
            ]);

            $logLevel = $after === 'breached' ? 'warning' : 'info';
            Log::channel('structured')->{$logLevel}('sla.transition.recorded', [
                'ticket_id' => $ticket->id,
                'metric' => $metric,
                'from' => $before,
                'to' => $after,
                'tenant_id' => $ticket->tenant_id,
            ]);
        }

        $policy = $ticket->slaPolicy;
        if ($policy) {
            $breachedMetrics = collect(['first_response', 'resolution'])
                ->filter(function (string $metric) use ($current, $policy) {
                    $state = Arr::get($current, "{$metric}.state");
                    if ($state !== 'breached') {
                        return false;
                    }

                    $breachedAt = Arr::get($current, "{$metric}.breached_at");
                    if (! $breachedAt) {
                        return false;
                    }

                    return Carbon::parse($breachedAt)->lt(now()->subMinutes($policy->alert_after_minutes));
                });

            if ($breachedMetrics->isNotEmpty()) {
                Log::channel('structured')->error('sla.alert.triggered', [
                    'ticket_id' => $ticket->id,
                    'tenant_id' => $ticket->tenant_id,
                    'policy_id' => $policy->id,
                    'metrics' => $breachedMetrics->values()->all(),
                ]);

                $ticket->next_sla_check_at = now()->addMinutes($policy->alert_after_minutes);
                $ticket->save();
            }
        }
    }
}
