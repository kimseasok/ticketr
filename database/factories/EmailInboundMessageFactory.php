<?php

namespace Database\Factories;

use App\Modules\Helpdesk\Models\EmailInboundMessage;
use App\Modules\Helpdesk\Models\EmailMailbox;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<EmailInboundMessage>
 */
class EmailInboundMessageFactory extends Factory
{
    protected $model = EmailInboundMessage::class;

    public function definition(): array
    {
        $messageId = Str::uuid()->toString();

        return [
            'tenant_id' => null,
            'brand_id' => null,
            'mailbox_id' => EmailMailbox::factory()->inbound(),
            'ticket_id' => null,
            'ticket_message_id' => null,
            'message_id' => $messageId,
            'thread_id' => Str::uuid()->toString(),
            'subject' => $this->faker->sentence,
            'from_name' => $this->faker->name,
            'from_email' => $this->faker->unique()->safeEmail,
            'to_recipients' => [$this->faker->companyEmail],
            'cc_recipients' => [],
            'bcc_recipients' => [],
            'text_body' => $this->faker->paragraph,
            'html_body' => '<p>'.$this->faker->paragraph.'</p>',
            'attachments_count' => 0,
            'status' => 'pending',
            'received_at' => now(),
            'processed_at' => null,
            'error_info' => null,
            'headers' => [
                'message-id' => $messageId,
            ],
        ];
    }

    public function configure(): self
    {
        return $this->afterMaking(function (EmailInboundMessage $message): void {
            if ($message->mailbox instanceof EmailMailbox) {
                $message->tenant_id ??= $message->mailbox->tenant_id;
                $message->brand_id ??= $message->mailbox->brand_id;
            }
        })->afterCreating(function (EmailInboundMessage $message): void {
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
