<?php

namespace Database\Factories;

use App\Modules\Helpdesk\Models\TicketStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TicketStatus>
 */
class TicketStatusFactory extends Factory
{
    protected $model = TicketStatus::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->words(2, true);

        return [
            'tenant_id' => null,
            'name' => Str::title($name),
            'slug' => Str::slug($name),
            'description' => $this->faker->sentence(),
            'sort_order' => $this->faker->numberBetween(0, 10),
            'is_default' => false,
            'first_response_minutes' => $this->faker->randomElement([null, 30, 60, 120]),
            'resolution_minutes' => $this->faker->randomElement([null, 240, 480, 720]),
            'metadata' => [
                'notify_roles' => $this->faker->randomElements(['Admin', 'Agent', 'Viewer'], $this->faker->numberBetween(0, 2)),
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
