<?php

namespace Database\Factories;

use App\Modules\Helpdesk\Models\KnowledgeBaseArticle;
use App\Modules\Helpdesk\Models\KnowledgeBaseCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<KnowledgeBaseArticle>
 */
class KnowledgeBaseArticleFactory extends Factory
{
    protected $model = KnowledgeBaseArticle::class;

    public function definition(): array
    {
        $title = $this->faker->sentence;

        return [
            'tenant_id' => null,
            'brand_id' => null,
            'category_id' => null,
            'title' => $title,
            'slug' => Str::slug($title . '-' . $this->faker->unique()->randomNumber()),
            'content' => $this->faker->paragraphs(3, true),
            'status' => KnowledgeBaseArticle::STATUS_DRAFT,
        ];
    }

    public function forCategory(KnowledgeBaseCategory $category): self
    {
        return $this->state(fn () => [
            'tenant_id' => $category->tenant_id,
            'brand_id' => $category->brand_id,
            'category_id' => $category->id,
        ]);
    }
}
