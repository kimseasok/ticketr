<?php

namespace App\Modules\Helpdesk\Services;

use App\Modules\Helpdesk\Models\Ticket;

class TicketSlaEvaluator
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public function evaluate(Ticket $ticket): array
    {
        return [
            'first_response' => $this->evaluateFirstResponse($ticket),
            'resolution' => $this->evaluateResolution($ticket),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function evaluateFirstResponse(Ticket $ticket): array
    {
        $dueAt = $ticket->first_response_due_at;
        $respondedAt = $ticket->first_responded_at;

        $state = 'pending';
        $breachedAt = null;

        if ($respondedAt) {
            $state = $dueAt && $respondedAt->greaterThan($dueAt) ? 'breached' : 'met';
            $breachedAt = $state === 'breached' ? $respondedAt : null;
        } elseif ($dueAt && now()->greaterThan($dueAt)) {
            $state = 'breached';
            $breachedAt = now();
        }

        return [
            'state' => $state,
            'due_at' => optional($dueAt)->toIso8601String(),
            'completed_at' => optional($respondedAt)->toIso8601String(),
            'breached_at' => optional($breachedAt)->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function evaluateResolution(Ticket $ticket): array
    {
        $dueAt = $ticket->resolution_due_at;
        $resolvedAt = $ticket->resolved_at ?? $ticket->closed_at ?? $ticket->archived_at;

        $state = 'pending';
        $breachedAt = null;

        if ($resolvedAt) {
            $state = $dueAt && $resolvedAt->greaterThan($dueAt) ? 'breached' : 'met';
            $breachedAt = $state === 'breached' ? $resolvedAt : null;
        } elseif ($dueAt && now()->greaterThan($dueAt)) {
            $state = 'breached';
            $breachedAt = now();
        }

        return [
            'state' => $state,
            'due_at' => optional($dueAt)->toIso8601String(),
            'completed_at' => optional($resolvedAt)->toIso8601String(),
            'breached_at' => optional($breachedAt)->toIso8601String(),
        ];
    }
}
