<?php

namespace Database\Factories;

use App\Modules\Helpdesk\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tenant>
 */
class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        $name = $this->faker->company;

        return [
            'name' => $name,
            'slug' => Str::slug($name . '-' . $this->faker->unique()->randomNumber()),
            'timezone' => 'UTC',
        ];
    }
}
