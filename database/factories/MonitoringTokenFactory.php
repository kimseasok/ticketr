<?php

namespace Database\Factories;

use App\Models\MonitoringToken;
use App\Modules\Helpdesk\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<MonitoringToken>
 */
class MonitoringTokenFactory extends Factory
{
    protected $model = MonitoringToken::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => $this->faker->unique()->word().' monitor',
            'token_hash' => hash('sha256', Str::random(40)),
            'scopes' => ['health:read'],
            'last_used_at' => null,
        ];
    }
}
