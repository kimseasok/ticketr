<?php

namespace Database\Factories;

use App\Modules\Helpdesk\Models\EmailMailbox;
use App\Modules\Helpdesk\Models\EmailOutboundMessage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<EmailOutboundMessage>
 */
class EmailOutboundMessageFactory extends Factory
{
    protected $model = EmailOutboundMessage::class;

    public function definition(): array
    {
        return [
            'tenant_id' => null,
            'brand_id' => null,
            'mailbox_id' => EmailMailbox::factory()->outbound(),
            'ticket_id' => null,
            'ticket_message_id' => null,
            'subject' => $this->faker->sentence,
            'to_recipients' => [$this->faker->safeEmail],
            'cc_recipients' => [],
            'bcc_recipients' => [],
            'text_body' => $this->faker->paragraph,
            'html_body' => '<p>'.$this->faker->paragraph.'</p>',
            'status' => 'queued',
            'attempts' => 0,
            'provider_message_id' => null,
            'scheduled_at' => now(),
            'last_attempted_at' => null,
            'sent_at' => null,
            'last_error' => null,
            'metadata' => [
                'message_guid' => Str::uuid()->toString(),
            ],
        ];
    }

    public function configure(): self
    {
        return $this->afterMaking(function (EmailOutboundMessage $message): void {
            if ($message->mailbox instanceof EmailMailbox) {
                $message->tenant_id ??= $message->mailbox->tenant_id;
                $message->brand_id ??= $message->mailbox->brand_id;
            }
        })->afterCreating(function (EmailOutboundMessage $message): void {
            if (! $message->relationLoaded('mailbox')) {
                $message->setRelation('mailbox', $message->mailbox()->first());
            }

            if ($message->mailbox instanceof EmailMailbox) {
                $message->tenant_id ??= $message->mailbox->tenant_id;
                $message->brand_id ??= $message->mailbox->brand_id;
                if ($message->isDirty(['tenant_id', 'brand_id'])) {
                    $message->save();
                }
            }
        });
    }

    public function forMailbox(EmailMailbox $mailbox): self
    {
        return $this->state(fn () => [
            'tenant_id' => $mailbox->tenant_id,
            'brand_id' => $mailbox->brand_id,
            'mailbox_id' => $mailbox->id,
        ]);
    }
}
