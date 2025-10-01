<?php

namespace App\Modules\Helpdesk\Observers;

use App\Modules\Helpdesk\Events\TicketSlaBreached;
use App\Modules\Helpdesk\Events\TicketSlaRecovered;
use App\Modules\Helpdesk\Models\Ticket;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class TicketSlaObserver
{
    public function updated(Ticket $ticket): void
    {
        $previous = $this->extractSlaState($ticket->getOriginal('metadata'));
        $current = $ticket->safeMetadata()['sla'] ?? [];

        foreach (['first_response', 'resolution'] as $metric) {
            $before = Arr::get($previous, "{$metric}.state", 'pending');
            $after = Arr::get($current, "{$metric}.state", 'pending');

            if ($after === $before) {
                continue;
            }

            if ($after === 'breached' && $before !== 'breached') {
                TicketSlaBreached::dispatch(
                    $ticket,
                    $metric,
                    Arr::get($current, "{$metric}.breached_at")
                );

                Log::channel('stack')->warning('ticket.sla.breached', [
                    'ticket_id' => $ticket->id,
                    'tenant_id' => $ticket->tenant_id,
                    'brand_id' => $ticket->brand_id,
                    'metric' => $metric,
                ]);
            }

            if ($after === 'met' && $before === 'breached') {
                TicketSlaRecovered::dispatch(
                    $ticket,
                    $metric,
                    Arr::get($current, "{$metric}.completed_at")
                );

                Log::channel('stack')->info('ticket.sla.recovered', [
                    'ticket_id' => $ticket->id,
                    'tenant_id' => $ticket->tenant_id,
                    'brand_id' => $ticket->brand_id,
                    'metric' => $metric,
                ]);
            }
        }
    }

    /**
     * @param  array<string,mixed>|string|null  $metadata
     * @return array<string,mixed>
     */
    private function extractSlaState(array|string|null $metadata): array
    {
        if (is_string($metadata)) {
            $decoded = json_decode($metadata, true) ?: [];
        } else {
            $decoded = $metadata ?? [];
        }

        return $decoded['sla'] ?? [];
    }
}
