<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Helpdesk\Models\Brand;
use App\Modules\Helpdesk\Models\Company;
use App\Modules\Helpdesk\Models\Contact;
use App\Modules\Helpdesk\Models\Tenant;
use App\Modules\Helpdesk\Models\Ticket;
use App\Policies\TicketPolicy;
use App\Support\Tenancy\TenantContext;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PolicyAccessTest extends TestCase
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
    public function test_role_based_access_controls_are_enforced(): void
    {
        $tenant = Tenant::factory()->create();
        $brand = Brand::factory()->for($tenant)->create();
        $otherBrand = Brand::factory()->for($tenant)->create();
        $company = Company::factory()->forBrand($brand)->create();
        $contact = Contact::factory()->forCompany($company)->create();
        $ticket = Ticket::factory()->forContact($contact)->create();

        app(TenantContext::class)->setTenantId($tenant->id);
        app(TenantContext::class)->setBrandId($brand->id);

        $admin = User::factory()->create([
            'tenant_id' => $tenant->id,
            'brand_id' => $brand->id,
        ]);
        $admin->assignRole('Admin');

        $agent = User::factory()->create([
            'tenant_id' => $tenant->id,
            'brand_id' => $brand->id,
        ]);
        $agent->assignRole('Agent');

        $this->assertTrue($agent->can('tickets.manage'));

        $agentOtherBrand = User::factory()->create([
            'tenant_id' => $tenant->id,
            'brand_id' => $otherBrand->id,
        ]);
        $agentOtherBrand->assignRole('Agent');

        $viewer = User::factory()->create([
            'tenant_id' => $tenant->id,
            'brand_id' => $brand->id,
        ]);
        $viewer->assignRole('Viewer');

        $policy = new TicketPolicy();

        $this->assertTrue($policy->update($admin, $ticket));
        $this->assertTrue($policy->view($admin, $ticket));

        $this->assertTrue($policy->update($agent, $ticket));
        $this->assertTrue($policy->view($agent, $ticket));

        $this->assertFalse($policy->view($agentOtherBrand, $ticket));
        $this->assertFalse($policy->update($agentOtherBrand, $ticket));

        $this->assertTrue($policy->view($viewer, $ticket));
        $this->assertFalse($policy->update($viewer, $ticket));

        $this->assertTrue($policy->assign($admin, $ticket));
        $this->assertTrue($policy->assign($agent, $ticket));
        $this->assertFalse($policy->assign($agentOtherBrand, $ticket));
        $this->assertFalse($policy->assign($viewer, $ticket));

        $this->assertTrue($policy->manageWatchers($admin, $ticket));
        $this->assertTrue($policy->manageWatchers($agent, $ticket));
        $this->assertFalse($policy->manageWatchers($agentOtherBrand, $ticket));
        $this->assertFalse($policy->manageWatchers($viewer, $ticket));
    }
}
