<?php

namespace Database\Factories;

use App\Models\User;
use App\Modules\Helpdesk\Models\Brand;
use App\Modules\Helpdesk\Models\Contact;
use App\Modules\Helpdesk\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    public function definition(): array
    {
        return [
            'tenant_id' => null,
            'brand_id' => null,
            'contact_id' => null,
            'company_id' => null,
            'created_by' => null,
            'assigned_to' => null,
            'subject' => $this->faker->sentence,
            'description' => $this->faker->paragraph,
            'status' => Ticket::STATUS_OPEN,
            'priority' => 'normal',
            'channel' => $this->faker->randomElement(['email', 'web', 'chat', 'phone']),
            'reference' => strtoupper(Str::random(10)),
            'metadata' => [
                'sentiment' => $this->faker->randomElement(['positive', 'neutral', 'negative']),
            ],
            'status_changed_at' => now(),
            'first_response_due_at' => now()->addHours(4),
            'resolution_due_at' => now()->addHours(24),
            'last_activity_at' => now(),
        ];
    }

    public function forContact(Contact $contact): self
    {
        return $this->state(fn () => [
            'tenant_id' => $contact->tenant_id,
            'brand_id' => $contact->brand_id,
            'contact_id' => $contact->id,
            'company_id' => $contact->company_id,
        ]);
    }

    public function forBrand(Brand $brand): self
    {
        return $this->state(fn () => [
            'tenant_id' => $brand->tenant_id,
            'brand_id' => $brand->id,
        ]);
    }

    public function assignedTo(User $user): self
    {
        return $this->state(fn () => [
            'assigned_to' => $user->id,
        ]);
    }

    public function createdBy(User $user): self
    {
        return $this->state(fn () => [
            'created_by' => $user->id,
        ]);
    }

    public function resolved(): self
    {
        return $this->state(fn () => [
            'status' => Ticket::STATUS_RESOLVED,
            'resolved_at' => now(),
        ]);
    }
}
