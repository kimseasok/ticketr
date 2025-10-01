<?php

namespace Database\Factories;

use App\Modules\Helpdesk\Models\Tenant;
use App\Modules\Helpdesk\Models\TicketMacro;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TicketMacro>
 */
class TicketMacroFactory extends Factory
{
    protected $model = TicketMacro::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->sentence(3);

        return [
            'tenant_id' => Tenant::factory(),
            'brand_id' => null,
            'name' => $name,
            'slug' => Str::slug($name) . '-' . $this->faker->randomNumber(4),
            'description' => $this->faker->sentence(),
            'body' => $this->faker->paragraph(),
            'visibility' => $this->faker->randomElement(['tenant', 'brand', 'private']),
            'metadata' => [
                'category' => $this->faker->randomElement(['support', 'billing', 'success']),
            ],
            'is_shared' => $this->faker->boolean(80),
        ];
    }
}
