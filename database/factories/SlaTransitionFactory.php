<?php

namespace Database\Factories;

use App\Modules\Helpdesk\Models\SlaPolicy;
use App\Modules\Helpdesk\Models\SlaTransition;
use App\Modules\Helpdesk\Models\Tenant;
use App\Modules\Helpdesk\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SlaTransition>
 */
class SlaTransitionFactory extends Factory
{
    protected $model = SlaTransition::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'ticket_id' => Ticket::factory(),
            'sla_policy_id' => SlaPolicy::factory(),
            'metric' => $this->faker->randomElement(['first_response', 'resolution']),
            'from_state' => 'pending',
            'to_state' => $this->faker->randomElement(['met', 'breached']),
            'transitioned_at' => now(),
            'context' => ['reason' => 'factory'],
        ];
    }
}
