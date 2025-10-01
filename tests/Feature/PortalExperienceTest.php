<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Helpdesk\Filament\Resources\BrandResource\Pages\CreateBrand;
use App\Modules\Helpdesk\Models\Brand;
use App\Modules\Helpdesk\Models\KnowledgeBaseArticle;
use App\Modules\Helpdesk\Models\KnowledgeBaseCategory;
use App\Modules\Helpdesk\Models\Tenant;
use App\Modules\Helpdesk\Models\Ticket;
use App\Support\Tenancy\TenantContext;
use Database\Seeders\RoleSeeder;
use Database\Seeders\TicketLifecycleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PortalExperienceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * @group Issue-10
     */
    public function test_portal_ticket_submission_creates_ticket_and_message(): void
    {
        $tenant = Tenant::factory()->create();
        app(TicketLifecycleSeeder::class)->runForTenant($tenant->id);
        $brand = Brand::factory()->for($tenant)->create(['slug' => 'portal-brand']);

        $response = $this->withSession(['_token' => 'test-token'])->post('/portal/'.$brand->slug.'/tickets', [
            '_token' => 'test-token',
            'name' => 'Portal User',
            'email' => 'portal@example.com',
            'subject' => 'Portal Support',
            'message' => 'I need help with my subscription.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $ticket = Ticket::query()->where('subject', 'Portal Support')->first();
        $this->assertNotNull($ticket);
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'brand_id' => $brand->id,
            'channel' => 'web',
        ]);

        $this->assertDatabaseHas('ticket_messages', [
            'ticket_id' => $ticket->id,
            'author_type' => 'contact',
            'visibility' => 'public',
        ]);
    }

    /**
     * @group Issue-10
     */
    public function test_portal_knowledge_base_listing(): void
    {
        $tenant = Tenant::factory()->create();
        $brand = Brand::factory()->for($tenant)->create(['slug' => 'docs-brand']);
        $category = KnowledgeBaseCategory::factory()->create([
            'tenant_id' => $tenant->id,
            'brand_id' => $brand->id,
        ]);
        KnowledgeBaseArticle::factory()->create([
            'tenant_id' => $tenant->id,
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'status' => KnowledgeBaseArticle::STATUS_PUBLISHED,
        ]);

        $this->get('/portal/'.$brand->slug)
            ->assertOk()
            ->assertSee('Knowledge base');
    }

    /**
     * @group Issue-10
     */
    public function test_filament_brand_resource_supports_theme_configuration(): void
    {
        $tenant = Tenant::factory()->create();
        $brand = Brand::factory()->for($tenant)->create();
        $admin = User::factory()->create(['tenant_id' => $tenant->id, 'brand_id' => $brand->id]);
        $admin->assignRole('Admin');

        app(TenantContext::class)->setTenantId($tenant->id);
        app(TenantContext::class)->setBrandId($brand->id);

        Livewire::actingAs($admin)
            ->test(CreateBrand::class)
            ->set('data', [
                'tenant_id' => $tenant->id,
                'name' => 'Portal Theme',
                'slug' => 'portal-theme',
                'domain' => 'https://portal-theme.test',
                'portal_domain' => 'https://portal-theme.test',
                'primary_color' => '#2563eb',
                'secondary_color' => '#1d4ed8',
                'accent_color' => '#f97316',
                'metadata' => ['welcome' => 'Hello!'],
            ])
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('brands', [
            'slug' => 'portal-theme',
            'primary_color' => '#2563eb',
        ]);
    }
}
