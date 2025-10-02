<?php

namespace App\Modules\Helpdesk\Services;

use App\Models\User;
use App\Modules\Helpdesk\Models\AutomationRule;
use App\Modules\Helpdesk\Models\AutomationRuleExecution;
use App\Modules\Helpdesk\Models\SlaPolicy;
use App\Modules\Helpdesk\Models\Ticket;
use App\Modules\Helpdesk\Models\TicketTag;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AutomationRuleEngine
{
    /** @var array<int,bool> */
    private static array $processingTickets = [];

    public function __construct(
        private readonly DatabaseManager $db,
        private readonly SlaPolicyService $slaPolicyService
    ) {
    }

    public function isProcessing(int $ticketId): bool
    {
        return self::$processingTickets[$ticketId] ?? false;
    }

    public function evaluate(Ticket $ticket, string $event): void
    {
        if ($this->isProcessing($ticket->id)) {
            return;
        }

        self::$processingTickets[$ticket->id] = true;

        try {
            $rules = AutomationRule::query()
                ->where('tenant_id', $ticket->tenant_id)
                ->active()
                ->where('event', $event)
                ->ordered()
                ->get();

            foreach ($rules as $rule) {
                if ($rule->brand_id !== null && $rule->brand_id !== $ticket->brand_id) {
                    $this->logExecution($rule, $ticket, $event, 'skipped', 'brand_mismatch');
                    continue;
                }

                $conditions = Collection::make($rule->conditions ?? []);
                $matches = $this->conditionsMatch($conditions, $ticket, $rule->match_type === 'all');

                if (! $matches) {
                    $this->logExecution($rule, $ticket, $event, 'skipped', 'conditions_not_met', [
                        'conditions' => $conditions,
                    ]);
                    continue;
                }

                $result = $this->executeActions($rule, $ticket);

                $this->logExecution($rule, $ticket, $event, $result['status'], $result['message'], $result['context']);

                $rule->forceFill(['last_run_at' => now()])->saveQuietly();
            }
        } finally {
            unset(self::$processingTickets[$ticket->id]);
        }
    }

    /**
     * @param  Collection<int,array<string,mixed>>  $conditions
     */
    private function conditionsMatch(Collection $conditions, Ticket $ticket, bool $requireAll): bool
    {
        if ($conditions->isEmpty()) {
            return true;
        }

        $results = $conditions->map(function (array $condition) use ($ticket) {
            $field = (string) Arr::get($condition, 'field');
            $operator = (string) Arr::get($condition, 'operator', 'equals');
            $value = Arr::get($condition, 'value');

            $actual = data_get($ticket, $field);

            return $this->compare($actual, $operator, $value);
        });

        return $requireAll ? $results->every(fn ($result) => (bool) $result) : $results->contains(true);
    }

    /**
     * @param  mixed  $actual
     * @param  mixed  $expected
     */
    private function compare(mixed $actual, string $operator, mixed $expected): bool
    {
        $operator = Str::of($operator)->lower()->toString();

        return match ($operator) {
            'equals' => $actual == $expected,
            'not_equals' => $actual != $expected,
            'in' => in_array($actual, (array) $expected, true),
            'not_in' => ! in_array($actual, (array) $expected, true),
            'contains' => is_string($actual) && is_string($expected) ? str_contains(Str::lower($actual), Str::lower($expected)) : false,
            default => false,
        };
    }

    /**
     * @return array{status:string,message:string,context:array<string,mixed>}
     */
    private function executeActions(AutomationRule $rule, Ticket $ticket): array
    {
        $context = [];

        try {
            $this->db->transaction(function () use ($rule, $ticket, &$context): void {
                $updates = [];
                $tagsToSync = null;

                foreach ($rule->actions as $action) {
                    $type = (string) Arr::get($action, 'type');

                    switch ($type) {
                        case 'set_priority':
                            $value = Arr::get($action, 'value');
                            if (in_array($value, ['low', 'normal', 'high', 'urgent'], true)) {
                                $updates['priority'] = $value;
                                $context['priority'] = $value;
                            }
                            break;
                        case 'set_status':
                            $value = Arr::get($action, 'value');
                            if (in_array($value, [Ticket::STATUS_OPEN, Ticket::STATUS_PENDING, Ticket::STATUS_RESOLVED, Ticket::STATUS_CLOSED, Ticket::STATUS_ARCHIVED], true)) {
                                $updates['status'] = $value;
                                $context['status'] = $value;
                            }
                            break;
                        case 'assign_agent':
                            $userId = (int) Arr::get($action, 'user_id');
                            if ($userId > 0) {
                                $user = User::query()
                                    ->where('tenant_id', $ticket->tenant_id)
                                    ->where('id', $userId)
                                    ->when($ticket->brand_id !== null, function ($query) use ($ticket): void {
                                        $query->where(function ($query) use ($ticket): void {
                                            $query->whereNull('brand_id')->orWhere('brand_id', $ticket->brand_id);
                                        });
                                    })
                                    ->first();

                                if ($user) {
                                    $updates['assigned_to'] = $user->id;
                                    $context['assigned_to'] = $user->id;
                                }
                            }
                            break;
                        case 'apply_sla':
                            $policyId = (int) Arr::get($action, 'sla_policy_id');
                            if ($policyId > 0) {
                                $policy = SlaPolicy::query()
                                    ->where('tenant_id', $ticket->tenant_id)
                                    ->find($policyId);

                                if ($policy) {
                                    $this->slaPolicyService->assignPolicy($ticket, $policy);
                                    $context['sla_policy_id'] = $policy->id;
                                }
                            }
                            break;
                        case 'add_tags':
                            $tagIds = Collection::make(Arr::get($action, 'tag_ids', []))
                                ->map(fn ($id) => (int) $id)
                                ->filter(fn ($id) => $id > 0)
                                ->unique()
                                ->values();

                            if ($tagIds->isNotEmpty()) {
                                $validTags = TicketTag::query()
                                    ->where('tenant_id', $ticket->tenant_id)
                                    ->whereIn('id', $tagIds)
                                    ->pluck('id')
                                    ->all();
                                $tagsToSync = $validTags;
                                $context['tag_ids'] = $validTags;
                            }
                            break;
                        default:
                            $context['ignored_actions'][] = $type;
                    }
                }

                if ($updates !== []) {
                    $ticket->fill($updates);
                }

                if ($tagsToSync !== null) {
                    $ticket->syncTags($tagsToSync, auth()->id());
                }

                $ticket->next_sla_check_at = $ticket->next_sla_check_at ?? now();
                $ticket->save();
            });
        } catch (\Throwable $exception) {
            Log::channel('structured')->error('automation.rule.failed', [
                'rule_id' => $rule->id,
                'ticket_id' => $ticket->id,
                'tenant_id' => $ticket->tenant_id,
                'message' => $exception->getMessage(),
            ]);

            return [
                'status' => 'failed',
                'message' => 'exception',
                'context' => ['exception' => $exception->getMessage()],
            ];
        }

        Log::channel('structured')->info('automation.rule.executed', [
            'rule_id' => $rule->id,
            'ticket_id' => $ticket->id,
            'tenant_id' => $ticket->tenant_id,
            'context' => $context,
        ]);

        return [
            'status' => 'matched',
            'message' => 'actions_executed',
            'context' => $context,
        ];
    }

    /**
     * @param  array<string,mixed>  $context
     */
    private function logExecution(AutomationRule $rule, Ticket $ticket, string $event, string $status, string $message, array $context = []): void
    {
        AutomationRuleExecution::create([
            'automation_rule_id' => $rule->id,
            'tenant_id' => $ticket->tenant_id,
            'ticket_id' => $ticket->id,
            'trigger_event' => $event,
            'status' => $status,
            'result' => $message,
            'context' => $context ?: null,
            'executed_at' => now(),
        ]);

        Log::channel('structured')->info('automation.rule.execution', [
            'rule_id' => $rule->id,
            'ticket_id' => $ticket->id,
            'tenant_id' => $ticket->tenant_id,
            'status' => $status,
            'message' => $message,
            'event' => $event,
        ]);
    }
}
