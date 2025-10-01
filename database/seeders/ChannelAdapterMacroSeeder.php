<?php

namespace Database\Seeders;

use App\Modules\Helpdesk\Models\Brand;
use App\Modules\Helpdesk\Models\ChannelAdapter;
use App\Modules\Helpdesk\Models\Tenant;
use App\Modules\Helpdesk\Models\TicketMacro;
use Illuminate\Database\Seeder;

class ChannelAdapterMacroSeeder extends Seeder
{
    public function run(): void
    {
        $tenants = Tenant::with('brands')->get();

        foreach ($tenants as $tenant) {
            $this->seedAdapters($tenant);
            $this->seedMacros($tenant);
        }
    }

    private function seedAdapters(Tenant $tenant): void
    {
        $defaults = [
            [
                'name' => 'Default Email Gateway',
                'slug' => 'email-gateway',
                'channel' => 'email',
                'provider' => 'smtp',
                'configuration' => [
                    'host' => 'smtp.example.test',
                    'port' => 587,
                    'username' => 'support@example.test',
                ],
            ],
            [
                'name' => 'Chat Widget Connector',
                'slug' => 'chat-widget',
                'channel' => 'chat',
                'provider' => 'websocket',
                'configuration' => [
                    'endpoint' => 'https://chat.example.test/webhook',
                    'shared_secret' => 'replace-me',
                ],
            ],
            [
                'name' => 'Voice Ingestion Bridge',
                'slug' => 'voice-bridge',
                'channel' => 'phone',
                'provider' => 'twilio',
                'configuration' => [
                    'account_sid' => 'ACxxxxxxxx',
                    'webhook' => 'https://voice.example.test/callback',
                ],
            ],
        ];

        /** @var Brand|null $brand */
        $brand = $tenant->brands->first();

        foreach ($defaults as $default) {
            ChannelAdapter::updateOrCreate([
                'tenant_id' => $tenant->id,
                'slug' => $default['slug'],
            ], array_merge($default, [
                'tenant_id' => $tenant->id,
                'brand_id' => $brand?->id,
                'metadata' => ['seeded' => true],
                'is_active' => true,
            ]));
        }
    }

    private function seedMacros(Tenant $tenant): void
    {
        $macros = [
            [
                'name' => 'First Response Acknowledgement',
                'slug' => 'acknowledge-ticket',
                'description' => 'Quick acknowledgement for new tickets.',
                'body' => "Hi {{contact.name}},\n\nThanks for reaching out! We're on it and will follow up shortly.",
                'visibility' => 'tenant',
            ],
            [
                'name' => 'Escalate to Tier 2',
                'slug' => 'escalate-tier-2',
                'description' => 'Flags the ticket for Tier 2 review with internal notes.',
                'body' => "Escalating to Tier 2.\n\nInternal Notes: {{notes}}",
                'visibility' => 'brand',
            ],
            [
                'name' => 'Close with CSAT Survey',
                'slug' => 'close-with-csat',
                'description' => 'Closes the ticket and invites customer feedback.',
                'body' => "We're marking this ticket as resolved. Please take a moment to rate your experience: {{survey_link}}",
                'visibility' => 'tenant',
            ],
        ];

        foreach ($macros as $macro) {
            TicketMacro::updateOrCreate([
                'tenant_id' => $tenant->id,
                'slug' => $macro['slug'],
            ], array_merge($macro, [
                'tenant_id' => $tenant->id,
                'brand_id' => $macro['visibility'] === 'brand' ? $tenant->brands->first()?->id : null,
                'metadata' => ['seeded' => true],
                'is_shared' => $macro['visibility'] !== 'private',
            ]));
        }
    }
}
