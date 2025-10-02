<?php

namespace Database\Factories;

use App\Modules\Helpdesk\Models\SlaPolicy;
use App\Modules\Helpdesk\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SlaPolicy>
 */
class SlaPolicyFactory extends Factory
{
    protected $model = SlaPolicy::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->sentence(2);

        return [
            'tenant_id' => Tenant::factory(),
            'brand_id' => null,
            'name' => $name,
            'slug' => Str::slug($name) . '-' . $this->faker->randomNumber(4),
            'description' => $this->faker->sentence(),
            'priority_scope' => $this->faker->randomElement(['low', 'normal', 'high', 'urgent', null]),
            'channel_scope' => $this->faker->randomElement(['email', 'web', 'chat', 'phone', null]),
            'first_response_minutes' => $this->faker->numberBetween(15, 120),
            'resolution_minutes' => $this->faker->numberBetween(120, 480),
            'grace_minutes' => $this->faker->numberBetween(0, 60),
            'is_active' => true,
            'alert_after_minutes' => $this->faker->numberBetween(15, 120),
        ];
    }
}
