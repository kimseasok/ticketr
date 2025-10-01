<?php

namespace Database\Factories;

use App\Modules\Helpdesk\Models\ChannelAdapter;
use App\Modules\Helpdesk\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ChannelAdapter>
 */
class ChannelAdapterFactory extends Factory
{
    protected $model = ChannelAdapter::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->company();

        return [
            'tenant_id' => Tenant::factory(),
            'brand_id' => null,
            'name' => $name,
            'slug' => Str::slug($name) . '-' . $this->faker->randomNumber(4),
            'channel' => $this->faker->randomElement(['email', 'web', 'chat', 'phone']),
            'provider' => $this->faker->randomElement(['zendesk', 'front', 'ms_teams', 'gmail']),
            'configuration' => [
                'endpoint' => $this->faker->url(),
                'token' => Str::random(12),
            ],
            'metadata' => [
                'region' => $this->faker->countryCode(),
            ],
            'is_active' => true,
            'last_synced_at' => now(),
        ];
    }
}
