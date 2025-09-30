<?php

namespace Database\Factories;

use App\Modules\Helpdesk\Models\TicketTag;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TicketTag>
 */
class TicketTagFactory extends Factory
{
    protected $model = TicketTag::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->word();

        return [
            'tenant_id' => null,
            'name' => Str::title($name),
            'slug' => Str::slug($name),
            'color' => $this->faker->safeHexColor(),
            'metadata' => [
                'source' => $this->faker->randomElement(['customer', 'automation', 'system']),
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
