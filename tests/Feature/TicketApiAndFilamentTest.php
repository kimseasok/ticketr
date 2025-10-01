<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Helpdesk\Filament\Resources\TicketResource\Pages\CreateTicket;
use App\Modules\Helpdesk\Models\Brand;
use App\Modules\Helpdesk\Models\Company;
use App\Modules\Helpdesk\Models\Contact;
use App\Modules\Helpdesk\Models\Tenant;
use App\Modules\Helpdesk\Models\Ticket;
use App\Modules\Helpdesk\Models\TicketCategory;
use App\Modules\Helpdesk\Models\TicketTag;
use App\Support\Tenancy\TenantContext;
use Database\Seeders\RoleSeeder;
use Database\Seeders\TicketLifecycleSeeder;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TicketApiAndFilamentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * @group A1-MD-01
     */
    public function test_ticket_api_crud_flow(): void
    {
        $tenant = Tenant::factory()->create();
        $brand = Brand::factory()->for($tenant)->create();
        $company = Company::factory()->forBrand($brand)->create();
        $contact = Contact::factory()->forCompany($company)->create();
        $category = TicketCategory::factory()->forTenant($tenant)->create();
        $tag = TicketTag::factory()->forTenant($tenant)->create();

        app(TicketLifecycleSeeder::class)->runForTenant($tenant->id);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'brand_id' => $brand->id,
        ]);
        $user->assignRole('Admin');

        $this->actingAs($user);

        $headers = [
            config('tenancy.tenant_header') => $tenant->id,
            config('tenancy.brand_header') => $brand->id,
        ];

        $payload = [
            'brand_id' => $brand->id,
            'contact_id' => $contact->id,
            'company_id' => $company->id,
            'subject' => 'API Ticket Subject',
            'description' => 'Created via API test',
            'priority' => 'high',
            'channel' => 'web',
            'category_ids' => [$category->id],
            'tag_ids' => [$tag->id],
        ];

        $createResponse = $this->withHeaders($headers)->postJson('/api/tickets', $payload);
        $createResponse->assertCreated();
        $ticketId = $createResponse->json('data.id');

        $this->withHeaders($headers)
            ->getJson('/api/tickets')
            ->assertOk()
            ->assertJsonFragment(['id' => $ticketId]);

        $this->withHeaders($headers)
            ->putJson("/api/tickets/{$ticketId}", [
                'status' => Ticket::STATUS_PENDING,
                'priority' => 'urgent',
            ])
            ->assertOk()
            ->assertJsonFragment(['status' => Ticket::STATUS_PENDING]);

        $this->withHeaders($headers)
            ->deleteJson("/api/tickets/{$ticketId}")
            ->assertStatus(202);
    }

    /**
     * @group A1-MD-01
     */
    public function test_filament_ticket_creation_and_validation(): void
    {
        $tenant = Tenant::factory()->create();
        $brand = Brand::factory()->for($tenant)->create();
        $company = Company::factory()->forBrand($brand)->create();
        $contact = Contact::factory()->forCompany($company)->create();
        $category = TicketCategory::factory()->forTenant($tenant)->create();
        $tag = TicketTag::factory()->forTenant($tenant)->create();

        app(TicketLifecycleSeeder::class)->runForTenant($tenant->id);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'brand_id' => $brand->id,
        ]);
        $user->assignRole('Agent');

        app(TenantContext::class)->setTenantId($tenant->id);
        app(TenantContext::class)->setBrandId($brand->id);

        Livewire::actingAs($user)
            ->test(CreateTicket::class)
            ->set('data', [
                'tenant_id' => $tenant->id,
                'brand_id' => $brand->id,
                'contact_id' => $contact->id,
                'company_id' => $company->id,
                'subject' => 'Filament Ticket',
                'description' => 'Created via Filament test',
                'status' => Ticket::STATUS_OPEN,
                'priority' => 'normal',
                'channel' => 'email',
                'category_ids' => [$category->id],
                'tag_ids' => [$tag->id],
            ])
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('tickets', [
            'subject' => 'Filament Ticket',
            'tenant_id' => $tenant->id,
        ]);

        $this->assertDatabaseHas('ticket_category_ticket', [
            'ticket_category_id' => $category->id,
        ]);
        $this->assertDatabaseHas('ticket_tag_ticket', [
            'ticket_tag_id' => $tag->id,
        ]);

        Livewire::actingAs($user)
            ->test(CreateTicket::class)
            ->set('data', [
                'tenant_id' => $tenant->id,
                'brand_id' => $brand->id,
                'subject' => '',
                'priority' => 'normal',
            ])
            ->call('create')
            ->assertHasErrors(['data.subject' => 'required']);
    }
}
