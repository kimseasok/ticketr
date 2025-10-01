<?php

namespace Database\Factories;

use App\Modules\Helpdesk\Models\Brand;
use App\Modules\Helpdesk\Models\EmailMailbox;
use App\Modules\Helpdesk\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<EmailMailbox>
 */
class EmailMailboxFactory extends Factory
{
    protected $model = EmailMailbox::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->company . ' Support';

        return [
            'tenant_id' => Tenant::factory(),
            'brand_id' => null,
            'name' => $name,
            'slug' => Str::slug($name . '-' . $this->faker->unique()->randomNumber()),
            'direction' => $this->faker->randomElement(['inbound', 'outbound', 'bidirectional']),
            'protocol' => $this->faker->randomElement(['imap', 'smtp']),
            'host' => $this->faker->domainName,
            'port' => $this->faker->randomElement([993, 995, 465, 587]),
            'encryption' => $this->faker->randomElement(['ssl', 'tls', null]),
            'username' => $this->faker->userName,
            'credentials' => ['password' => 'secret'],
            'settings' => [
                'folder' => 'INBOX',
                'sender_name' => $name,
            ],
            'sync_state' => [
                'last_uid' => null,
            ],
            'is_active' => true,
        ];
    }

    public function inbound(): self
    {
        return $this->state(fn () => [
            'direction' => 'inbound',
            'protocol' => 'imap',
        ]);
    }

    public function outbound(): self
    {
        return $this->state(fn () => [
            'direction' => 'outbound',
            'protocol' => 'smtp',
        ]);
    }

    public function forBrand(Brand $brand): self
    {
        return $this->state(fn () => [
            'tenant_id' => $brand->tenant_id,
            'brand_id' => $brand->id,
        ]);
    }
}
