<?php

namespace Database\Factories;

use App\Modules\Helpdesk\Models\TicketStatus;
use App\Modules\Helpdesk\Models\TicketWorkflowTransition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TicketWorkflowTransition>
 */
class TicketWorkflowTransitionFactory extends Factory
{
    protected $model = TicketWorkflowTransition::class;

    public function definition(): array
    {
        return [
            'tenant_id' => null,
            'from_status_id' => TicketStatus::factory(),
            'to_status_id' => TicketStatus::factory(),
            'requires_comment' => $this->faker->boolean(20),
            'requires_resolution_note' => $this->faker->boolean(10),
            'metadata' => [
                'notify_roles' => $this->faker->randomElements(['Admin', 'Agent'], $this->faker->numberBetween(0, 2)),
            ],
        ];
    }
}
