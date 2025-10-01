<?php

namespace Database\Seeders;

use App\Modules\Helpdesk\Models\Tenant;
use App\Modules\Helpdesk\Models\TicketPriority;
use App\Modules\Helpdesk\Models\TicketStatus;
use App\Modules\Helpdesk\Models\TicketWorkflowTransition;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class TicketLifecycleSeeder extends Seeder
{
    public function run(): void
    {
        $tenants = Tenant::query()->with('brands')->get();

        foreach ($tenants as $tenant) {
            $this->seedForTenant($tenant->id);
        }
    }

    public function runForTenant(int $tenantId): void
    {
        $this->seedForTenant($tenantId);
    }

    private function seedForTenant(int $tenantId): void
    {
        DB::transaction(function () use ($tenantId) {
            $statusRecords = collect(config('ticketing.defaults.statuses'))
                ->mapWithKeys(function (array $status) use ($tenantId) {
                    $record = TicketStatus::updateOrCreate(
                        [
                            'tenant_id' => $tenantId,
                            'slug' => $status['slug'],
                        ],
                        [
                            'name' => $status['name'],
                            'description' => Arr::get($status, 'description'),
                            'sort_order' => Arr::get($status, 'sort_order', 0),
                            'is_default' => Arr::get($status, 'is_default', false),
                            'first_response_minutes' => Arr::get($status, 'first_response_minutes'),
                            'resolution_minutes' => Arr::get($status, 'resolution_minutes'),
                        ]
                    );

                    return [$status['slug'] => $record];
                });

            collect(config('ticketing.defaults.priorities'))
                ->each(function (array $priority) use ($tenantId) {
                    TicketPriority::updateOrCreate(
                        [
                            'tenant_id' => $tenantId,
                            'slug' => $priority['slug'],
                        ],
                        [
                            'name' => $priority['name'],
                            'color' => Arr::get($priority, 'color'),
                            'sort_order' => Arr::get($priority, 'sort_order', 0),
                            'is_default' => Arr::get($priority, 'is_default', false),
                            'first_response_minutes' => Arr::get($priority, 'first_response_minutes'),
                            'resolution_minutes' => Arr::get($priority, 'resolution_minutes'),
                        ]
                    );
                });

            $transitionDefinitions = collect(config('ticketing.defaults.transitions'));

            $existingTransitions = TicketWorkflowTransition::query()
                ->where('tenant_id', $tenantId)
                ->get()
                ->keyBy(fn (TicketWorkflowTransition $transition) => implode('-', [
                    $transition->from_status_id,
                    $transition->to_status_id,
                ]));

            $transitionDefinitions->each(function (array $transition) use ($tenantId, $statusRecords, $existingTransitions) {
                $from = $statusRecords[$transition['from']] ?? null;
                $to = $statusRecords[$transition['to']] ?? null;

                if (! $from || ! $to) {
                    return;
                }

                $key = implode('-', [$from->id, $to->id]);

                TicketWorkflowTransition::updateOrCreate(
                    [
                        'tenant_id' => $tenantId,
                        'from_status_id' => $from->id,
                        'to_status_id' => $to->id,
                    ],
                    [
                        'requires_comment' => (bool) Arr::get($transition, 'requires_comment', false),
                        'requires_resolution_note' => (bool) Arr::get($transition, 'requires_resolution_note', false),
                    ]
                );

                $existingTransitions->forget($key);
            });

            if ($existingTransitions->isNotEmpty()) {
                $obsoleteIds = $existingTransitions->pluck('id');

                if ($obsoleteIds->isNotEmpty()) {
                    TicketWorkflowTransition::query()
                        ->whereIn('id', $obsoleteIds)
                        ->delete();
                }
            }
        });
    }
}
