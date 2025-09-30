<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Helpdesk\Models\Brand;
use App\Modules\Helpdesk\Models\Company;
use App\Modules\Helpdesk\Models\Contact;
use App\Modules\Helpdesk\Models\Tenant;
use App\Modules\Helpdesk\Models\Ticket;
use App\Modules\Helpdesk\Models\TicketCategory;
use App\Modules\Helpdesk\Models\TicketTag;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketTaxonomyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @group A1-DB-02
     */
    public function test_ticket_can_sync_categories_and_tags_within_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        $brand = Brand::factory()->for($tenant)->create();
        $company = Company::factory()->forBrand($brand)->create();
        $contact = Contact::factory()->forCompany($company)->create();
        $agent = User::factory()->create([
            'tenant_id' => $tenant->id,
            'brand_id' => $brand->id,
        ]);

        app(TenantContext::class)->setTenantId($tenant->id);
        app(TenantContext::class)->setBrandId($brand->id);

        $category = TicketCategory::factory()->forTenant($tenant)->create();
        $tag = TicketTag::factory()->forTenant($tenant)->create();
        $otherCategory = TicketCategory::factory()->forTenant(Tenant::factory()->create())->create();

        $ticket = Ticket::factory()
            ->forContact($contact)
            ->createdBy($agent)
            ->assignedTo($agent)
            ->create();

        $ticket->syncCategories([$category->id, $otherCategory->id], $agent->id);
        $ticket->syncTags([$tag->id], $agent->id);

        $this->assertDatabaseHas('ticket_category_ticket', [
            'ticket_id' => $ticket->id,
            'ticket_category_id' => $category->id,
            'tenant_id' => $tenant->id,
        ]);

        $this->assertDatabaseMissing('ticket_category_ticket', [
            'ticket_id' => $ticket->id,
            'ticket_category_id' => $otherCategory->id,
        ]);

        $this->assertDatabaseHas('ticket_tag_ticket', [
            'ticket_id' => $ticket->id,
            'ticket_tag_id' => $tag->id,
            'tenant_id' => $tenant->id,
        ]);
    }
}
