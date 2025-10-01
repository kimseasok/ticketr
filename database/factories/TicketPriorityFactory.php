<?php

namespace Database\Factories;

use App\Modules\Helpdesk\Models\TicketPriority;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TicketPriority>
 */
class TicketPriorityFactory extends Factory
{
    protected $model = TicketPriority::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->word();

        return [
            'tenant_id' => null,
            'name' => Str::title($name),
            'slug' => Str::slug($name),
            'color' => $this->faker->safeHexColor(),
            'sort_order' => $this->faker->numberBetween(0, 10),
            'is_default' => false,
            'first_response_minutes' => $this->faker->randomElement([30, 60, 120, 240]),
            'resolution_minutes' => $this->faker->randomElement([480, 720, 1440]),
            'metadata' => [
                'escalation' => $this->faker->randomElement(['none', 'manager', 'director']),
            ],
        ];
    }

    public function default(): self
    {
        return $this->state(fn () => ['is_default' => true]);
    }

    public function forTenant(\App\Modules\Helpdesk\Models\Tenant $tenant): self
    {
        return $this->state(fn () => [
            'tenant_id' => $tenant->id,
        ]);
    }
}
