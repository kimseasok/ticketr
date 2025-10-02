<?php

namespace Database\Factories;

use App\Modules\Helpdesk\Models\AutomationRule;
use App\Modules\Helpdesk\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AutomationRule>
 */
class AutomationRuleFactory extends Factory
{
    protected $model = AutomationRule::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->sentence(3);

        return [
            'tenant_id' => Tenant::factory(),
            'brand_id' => null,
            'name' => $name,
            'slug' => Str::slug($name) . '-' . $this->faker->randomNumber(4),
            'event' => $this->faker->randomElement(['ticket.created', 'ticket.updated', 'sla.breached']),
            'match_type' => $this->faker->randomElement(['all', 'any']),
            'conditions' => [
                [
                    'field' => 'priority',
                    'operator' => 'equals',
                    'value' => 'high',
                ],
            ],
            'actions' => [
                [
                    'type' => 'set_priority',
                    'value' => 'urgent',
                ],
            ],
            'run_order' => $this->faker->numberBetween(0, 5),
            'is_active' => true,
        ];
    }
}
