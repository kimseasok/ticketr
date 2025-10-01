<?php

namespace App\Modules\Helpdesk\Services;

use App\Models\User;
use App\Modules\Helpdesk\Models\Ticket;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class TicketBulkActionService
{
    /**
     * @param  array<int>  $ticketIds
     * @param  array<int, array<string, mixed>>  $actions
     * @return array{processed:int, skipped:int, errors:array<int, array<string, mixed>>}
     */
    public function apply(User $user, array $ticketIds, array $actions): array
    {
        $results = [
            'processed' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        $tickets = Ticket::query()
            ->where('tenant_id', $user->tenant_id)
            ->when($user->brand_id, function ($query) use ($user) {
                $query->where(function ($inner) use ($user) {
                    $inner->whereNull('brand_id')->orWhere('brand_id', $user->brand_id);
                });
            })
            ->whereIn('id', $ticketIds)
            ->get();

        foreach ($tickets as $ticket) {
            if (Gate::forUser($user)->denies('update', $ticket)) {
                $results['skipped']++;
                $results['errors'][] = [
                    'ticket_id' => $ticket->id,
                    'reason' => 'unauthorized',
                ];
                continue;
            }

            try {
                $this->applyActionsToTicket($ticket, $actions, $user);
                $ticket->save();

                $results['processed']++;
            } catch (ValidationException $exception) {
                $results['skipped']++;
                $results['errors'][] = [
                    'ticket_id' => $ticket->id,
                    'reason' => 'validation_failed',
                    'messages' => $exception->errors(),
                ];
            } catch (InvalidArgumentException $exception) {
                $results['skipped']++;
                $results['errors'][] = [
                    'ticket_id' => $ticket->id,
                    'reason' => 'invalid_action',
                    'messages' => [$exception->getMessage()],
                ];
            }
        }

        return $results;
    }

    /**
     * @param  array<int, array<string, mixed>>  $actions
     */
    private function applyActionsToTicket(Ticket $ticket, array $actions, User $actor): void
    {
        foreach ($actions as $action) {
            $type = Arr::get($action, 'type');

            if (! is_string($type)) {
                throw new InvalidArgumentException('Action type is required.');
            }

            match ($type) {
                'assign' => $this->assignTicket($ticket, $action, $actor),
                'status' => $this->updateStatus($ticket, $action),
                'sla' => $this->updateSla($ticket, $action),
                default => throw new InvalidArgumentException(sprintf('Unsupported action type [%s]', $type)),
            };
        }

        Log::channel('stack')->info('ticket.bulk_actions_applied', [
            'ticket_id' => $ticket->id,
            'tenant_id' => $ticket->tenant_id,
            'brand_id' => $ticket->brand_id,
            'actions' => array_map(fn ($action) => Arr::only($action, ['type']), $actions),
        ]);
    }

    private function assignTicket(Ticket $ticket, array $action, User $actor): void
    {
        $assigneeId = Arr::get($action, 'assignee_id');
        if (! is_int($assigneeId)) {
            throw new InvalidArgumentException('assignee_id must be provided for assign action.');
        }

        $assignee = User::query()
            ->where('tenant_id', $ticket->tenant_id)
            ->when($ticket->brand_id, fn ($query) => $query->where('brand_id', $ticket->brand_id))
            ->find($assigneeId);

        if (! $assignee) {
            throw ValidationException::withMessages([
                'assignee_id' => ['The selected assignee is invalid for this tenant or brand.'],
            ]);
        }

        $ticket->assigned_to = $assignee->id;
        $ticket->metadata = array_merge($ticket->metadata ?? [], [
            'last_assignment_actor' => $actor->id,
        ]);
    }

    private function updateStatus(Ticket $ticket, array $action): void
    {
        $status = Arr::get($action, 'status');

        if (! is_string($status) || $status === '') {
            throw new InvalidArgumentException('status must be provided for status action.');
        }

        $ticket->status = $status;
    }

    private function updateSla(Ticket $ticket, array $action): void
    {
        $resolutionDueAt = Arr::get($action, 'resolution_due_at');
        $firstResponseDueAt = Arr::get($action, 'first_response_due_at');

        if ($resolutionDueAt === null && $firstResponseDueAt === null) {
            throw new InvalidArgumentException('An SLA action requires resolution_due_at or first_response_due_at.');
        }

        if ($resolutionDueAt !== null) {
            $ticket->resolution_due_at = $this->parseDate($resolutionDueAt);
        }

        if ($firstResponseDueAt !== null) {
            $ticket->first_response_due_at = $this->parseDate($firstResponseDueAt);
        }
    }

    private function parseDate(mixed $value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if (is_string($value)) {
            return (string) Str::of($value)->trim();
        }

        throw new InvalidArgumentException('Date values must be strings or DateTime instances.');
    }
}
