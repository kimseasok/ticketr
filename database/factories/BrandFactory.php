<?php

namespace Database\Factories;

use App\Modules\Helpdesk\Models\Brand;
use App\Modules\Helpdesk\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Brand>
 */
class BrandFactory extends Factory
{
    protected $model = Brand::class;

    public function definition(): array
    {
        $name = $this->faker->company . ' Support';

        return [
            'tenant_id' => Tenant::factory(),
            'name' => $name,
            'slug' => Str::slug($name . '-' . $this->faker->unique()->randomNumber()),
            'domain' => sprintf('https://%s', $this->faker->domainName()),
            'primary_color' => $this->faker->hexColor,
            'secondary_color' => $this->faker->hexColor,
            'accent_color' => $this->faker->hexColor,
            'logo_url' => $this->faker->imageUrl(width: 200, height: 60, category: 'business'),
            'portal_domain' => sprintf('https://%s', $this->faker->domainName()),
        ];
    }
}
