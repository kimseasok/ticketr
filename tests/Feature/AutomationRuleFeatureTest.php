<?php

namespace Tests\Feature;

use App\Modules\Helpdesk\Filament\Resources\AutomationRuleResource\Pages\CreateAutomationRule;
use App\Modules\Helpdesk\Models\AutomationRule;
use App\Modules\Helpdesk\Models\Brand;
use App\Modules\Helpdesk\Models\SlaPolicy;
use App\Modules\Helpdesk\Models\Tenant;
use App\Modules\Helpdesk\Models\TicketTag;
use App\Support\Tenancy\TenantContext;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AutomationRuleFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * @group TKT-AUT-MD-02
     */
    public function test_agent_can_manage_automation_rules_via_api(): void
    {
        $tenant = Tenant::factory()->create();
        $brand = Brand::factory()->for($tenant)->create();
        $policy = SlaPolicy::factory()->create([
            'tenant_id' => $tenant->id,
            'brand_id' => $brand->id,
        ]);
        $tag = TicketTag::factory()->forTenant($tenant)->create();

        app(TenantContext::class)->setTenantId($tenant->id);
        app(TenantContext::class)->setBrandId($brand->id);

        $agent = $this->createUserWithRole('Agent', $tenant->id, $brand->id);

        $headers = [
            config('tenancy.tenant_header') => $tenant->id,
            config('tenancy.brand_header') => $brand->id,
        ];

        $payload = [
            'name' => 'Auto escalate',
            'slug' => 'auto-escalate',
            'event' => 'ticket.created',
            'match_type' => 'all',
            'conditions' => [
                ['field' => 'channel', 'operator' => 'equals', 'value' => 'email'],
            ],
            'actions' => [
                ['type' => 'set_priority', 'value' => 'urgent'],
                ['type' => 'apply_sla', 'sla_policy_id' => $policy->id],
                ['type' => 'add_tags', 'tag_ids' => [$tag->id]],
            ],
        ];

        $this->actingAs($agent);

        $create = $this->withHeaders($headers)->postJson('/api/automation/rules', $payload);
        $create->assertCreated();

        $ruleId = $create->json('data.id');
        $this->assertDatabaseHas('automation_rules', [
            'id' => $ruleId,
            'tenant_id' => $tenant->id,
        ]);

        $this->assertDatabaseHas('automation_rule_versions', [
            'automation_rule_id' => $ruleId,
            'version' => 1,
        ]);

        $this->withHeaders($headers)
            ->putJson("/api/automation/rules/{$ruleId}", [
                'is_active' => false,
                'actions' => [
                    ['type' => 'set_status', 'value' => 'pending'],
                ],
            ])
            ->assertOk()
            ->assertJsonFragment(['is_active' => false]);

        $this->assertDatabaseHas('automation_rule_versions', [
            'automation_rule_id' => $ruleId,
            'version' => 2,
        ]);

        $this->withHeaders($headers)
            ->deleteJson("/api/automation/rules/{$ruleId}")
            ->assertNoContent();

        $this->assertSoftDeleted('automation_rules', ['id' => $ruleId]);
    }

    /**
     * @group TKT-AUT-MD-02
     */
    public function test_filament_can_create_automation_rule(): void
    {
        $tenant = Tenant::factory()->create();
        $brand = Brand::factory()->for($tenant)->create();
        $policy = SlaPolicy::factory()->create([
            'tenant_id' => $tenant->id,
            'brand_id' => $brand->id,
        ]);

        app(TenantContext::class)->setTenantId($tenant->id);
        app(TenantContext::class)->setBrandId($brand->id);

        $agent = $this->createUserWithRole('Agent', $tenant->id, $brand->id);

        Livewire::actingAs($agent)
            ->test(CreateAutomationRule::class)
            ->set('data', [
                'tenant_id' => $tenant->id,
                'brand_id' => $brand->id,
                'name' => 'Filament Rule',
                'slug' => 'filament-rule',
                'event' => 'ticket.created',
                'match_type' => 'all',
                'conditions' => [
                    ['field' => 'priority', 'operator' => 'equals', 'value' => 'high'],
                ],
                'actions' => [
                    ['type' => 'set_priority', 'value' => 'urgent'],
                    ['type' => 'apply_sla', 'sla_policy_id' => $policy->id],
                ],
            ])
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('automation_rules', [
            'slug' => 'filament-rule',
            'tenant_id' => $tenant->id,
        ]);
    }

    /**
     * @group TKT-AUT-MD-02
     */
    public function test_viewer_cannot_delete_rules(): void
    {
        $tenant = Tenant::factory()->create();
        $brand = Brand::factory()->for($tenant)->create();
        $rule = AutomationRule::factory()->create([
            'tenant_id' => $tenant->id,
            'brand_id' => $brand->id,
        ]);

        app(TenantContext::class)->setTenantId($tenant->id);
        app(TenantContext::class)->setBrandId($brand->id);

        $viewer = $this->createUserWithRole('Viewer', $tenant->id, $brand->id);

        $headers = [
            config('tenancy.tenant_header') => $tenant->id,
            config('tenancy.brand_header') => $brand->id,
        ];

        $this->actingAs($viewer)
            ->withHeaders($headers)
            ->deleteJson("/api/automation/rules/{$rule->id}")
            ->assertForbidden();
    }

    private function createUserWithRole(string $role, int $tenantId, ?int $brandId)
    {
        $user = \App\Models\User::factory()->create([
            'tenant_id' => $tenantId,
            'brand_id' => $brandId,
        ]);
        $user->assignRole($role);

        return $user;
    }
}
