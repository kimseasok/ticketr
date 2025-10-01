<?php

namespace Database\Factories;

use App\Modules\Helpdesk\Models\Ticket;
use App\Modules\Helpdesk\Models\TicketMessage;
use App\Modules\Helpdesk\Models\TicketParticipant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TicketParticipant>
 */
class TicketParticipantFactory extends Factory
{
    protected $model = TicketParticipant::class;

    public function definition(): array
    {
        return [
            'tenant_id' => null,
            'brand_id' => null,
            'ticket_id' => null,
            'last_message_id' => null,
            'participant_type' => 'user',
            'participant_id' => null,
            'role' => $this->faker->randomElement(['agent', 'requester']),
            'visibility' => $this->faker->randomElement(['internal', 'external']),
            'last_seen_at' => now(),
            'last_typing_at' => null,
            'is_muted' => false,
            'metadata' => [],
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

    public function lastMessage(TicketMessage $message): self
    {
        return $this->state(fn () => [
            'last_message_id' => $message->id,
        ]);
    }
}
