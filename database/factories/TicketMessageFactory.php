<?php

namespace Database\Factories;

use App\Modules\Helpdesk\Models\Ticket;
use App\Modules\Helpdesk\Models\TicketMessage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TicketMessage>
 */
class TicketMessageFactory extends Factory
{
    protected $model = TicketMessage::class;

    public function definition(): array
    {
        return [
            'tenant_id' => null,
            'brand_id' => null,
            'ticket_id' => null,
            'author_type' => 'user',
            'author_id' => null,
            'visibility' => 'public',
            'channel' => $this->faker->randomElement(['email', 'web', 'chat']),
            'external_id' => Str::uuid()->toString(),
            'attachments_count' => 0,
            'body' => $this->faker->paragraph,
            'metadata' => [
                'source' => $this->faker->randomElement(['inbound', 'outbound']),
            ],
            'posted_at' => now(),
        ];
    }

    public function forTicket(Ticket $ticket): self
    {
        return $this->state(fn () => [
            'tenant_id' => $ticket->tenant_id,
            'brand_id' => $ticket->brand_id,
            'ticket_id' => $ticket->id,
        ]);
    }
}
