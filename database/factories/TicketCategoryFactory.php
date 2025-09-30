<?php

namespace Database\Factories;

use App\Modules\Helpdesk\Models\TicketCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TicketCategory>
 */
class TicketCategoryFactory extends Factory
{
    protected $model = TicketCategory::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->words(2, true);

        return [
            'tenant_id' => null,
            'name' => Str::title($name),
            'slug' => Str::slug($name),
            'color' => $this->faker->safeHexColor(),
            'sort_order' => $this->faker->numberBetween(0, 10),
            'is_default' => false,
            'description' => $this->faker->sentence(),
            'metadata' => [
                'icon' => $this->faker->randomElement(['tag', 'bolt', 'chat']),
            ],
        ];
    }

    public function forTenant(\App\Modules\Helpdesk\Models\Tenant $tenant): self
    {
        return $this->state(fn () => [
            'tenant_id' => $tenant->id,
        ]);
    }
}
