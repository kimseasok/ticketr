<?php

namespace Database\Factories;

use App\Modules\Helpdesk\Models\Brand;
use App\Modules\Helpdesk\Models\KnowledgeBaseCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<KnowledgeBaseCategory>
 */
class KnowledgeBaseCategoryFactory extends Factory
{
    protected $model = KnowledgeBaseCategory::class;

    public function definition(): array
    {
        $name = $this->faker->words(3, true);

        return [
            'tenant_id' => null,
            'brand_id' => null,
            'name' => ucfirst($name),
            'slug' => Str::slug($name . '-' . $this->faker->unique()->randomNumber()),
            'description' => $this->faker->sentence,
        ];
    }

    public function forBrand(Brand $brand): self
    {
        return $this->state(fn () => [
            'tenant_id' => $brand->tenant_id,
            'brand_id' => $brand->id,
        ]);
    }
}
