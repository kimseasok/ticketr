<?php

namespace Tests\Feature;

use App\Modules\Helpdesk\Models\Brand;
use App\Modules\Helpdesk\Models\KnowledgeBaseArticle;
use App\Modules\Helpdesk\Models\KnowledgeBaseCategory;
use App\Modules\Helpdesk\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KnowledgeBaseArticleCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_knowledge_base_article_crud_operations(): void
    {
        $tenant = Tenant::factory()->create();
        $brand = Brand::factory()->for($tenant)->create();
        $category = KnowledgeBaseCategory::factory()->forBrand($brand)->create();

        $article = KnowledgeBaseArticle::factory()->forCategory($category)->create([
            'title' => 'Troubleshooting Login Issues',
            'status' => KnowledgeBaseArticle::STATUS_PUBLISHED,
        ]);

        $this->assertDatabaseHas('knowledge_base_articles', [
            'id' => $article->id,
            'title' => 'Troubleshooting Login Issues',
        ]);

        $article->update([
            'status' => KnowledgeBaseArticle::STATUS_ARCHIVED,
        ]);

        $this->assertDatabaseHas('knowledge_base_articles', [
            'id' => $article->id,
            'status' => KnowledgeBaseArticle::STATUS_ARCHIVED,
        ]);

        $articleId = $article->id;
        $article->delete();

        $this->assertDatabaseMissing('knowledge_base_articles', [
            'id' => $articleId,
        ]);
    }
}
