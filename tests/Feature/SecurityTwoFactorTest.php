<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Helpdesk\Models\Brand;
use App\Modules\Helpdesk\Models\Tenant;
use App\Support\Security\TotpService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SecurityTwoFactorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * @group Issue-11
     */
    public function test_user_can_enroll_confirm_and_disable_two_factor(): void
    {
        $tenant = Tenant::factory()->create();
        $brand = Brand::factory()->for($tenant)->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id, 'brand_id' => $brand->id]);
        $user->assignRole('Admin');

        $this->actingAs($user);

        $enrollResponse = $this->postJson('/api/security/two-factor');
        $enrollResponse->assertOk();
        $secret = $enrollResponse->json('secret');

        $code = app(TotpService::class)->generateCode($secret, time());

        $this->postJson('/api/security/two-factor/confirm', ['code' => $code])
            ->assertOk()
            ->assertJsonFragment(['message' => 'Two-factor authentication confirmed']);

        $this->assertNotNull($user->fresh()->two_factor_confirmed_at);

        $this->deleteJson('/api/security/two-factor')
            ->assertOk()
            ->assertJsonFragment(['message' => 'Two-factor authentication disabled']);

        $this->assertNull($user->fresh()->two_factor_secret);
    }

    /**
     * @group Issue-11
     */
    public function test_invalid_two_factor_code_is_rejected(): void
    {
        $tenant = Tenant::factory()->create();
        $brand = Brand::factory()->for($tenant)->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id, 'brand_id' => $brand->id]);
        $user->assignRole('Admin');

        $this->actingAs($user);

        $this->postJson('/api/security/two-factor')->assertOk();

        $this->postJson('/api/security/two-factor/confirm', ['code' => '123456'])
            ->assertStatus(422);
    }

    /**
     * @group Issue-11
     */
    public function test_ip_allowlist_blocks_unapproved_addresses(): void
    {
        $tenant = Tenant::factory()->create();
        $brand = Brand::factory()->for($tenant)->create();
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'brand_id' => $brand->id,
            'ip_allowlist' => ['203.0.113.5'],
        ]);
        $user->assignRole('Admin');

        $this->actingAs($user);

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.50'])
            ->getJson('/api/tickets')
            ->assertStatus(403);
    }
}
