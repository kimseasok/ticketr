<?php

namespace Tests\Feature;

use App\Models\MonitoringToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthEndpointTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @group Issue-12
     */
    public function test_health_endpoint_requires_valid_token(): void
    {
        MonitoringToken::factory()->create([
            'token_hash' => hash('sha256', 'secret-token'),
        ]);

        $this->getJson('/api/health')->assertUnauthorized();

        $this->withHeaders(['X-Monitoring-Token' => 'secret-token'])
            ->getJson('/api/health')
            ->assertOk()
            ->assertJsonStructure(['database', 'redis', 'queue', 'scout', 'timestamp']);
    }

    /**
     * @group Issue-12
     */
    public function test_health_endpoint_rejects_ip_not_on_allowlist(): void
    {
        config(['monitoring.allowed_ips' => ['10.0.0.2']]);

        MonitoringToken::factory()->create([
            'token_hash' => hash('sha256', 'other-token'),
        ]);

        $this->withHeaders(['X-Monitoring-Token' => 'other-token'])
            ->getJson('/api/health')
            ->assertForbidden();
    }
}
