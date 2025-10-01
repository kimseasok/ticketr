<?php

namespace Database\Factories;

use App\Modules\Helpdesk\Models\EmailAttachment;
use App\Modules\Helpdesk\Models\EmailInboundMessage;
use App\Modules\Helpdesk\Models\EmailMailbox;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<EmailAttachment>
 */
class EmailAttachmentFactory extends Factory
{
    protected $model = EmailAttachment::class;

    public function definition(): array
    {
        $filename = $this->faker->unique()->slug . '.pdf';

        return [
            'tenant_id' => null,
            'brand_id' => null,
            'mailbox_id' => EmailMailbox::factory(),
            'message_type' => EmailInboundMessage::class,
            'message_id' => EmailInboundMessage::factory(),
            'attachment_id' => null,
            'filename' => $filename,
            'mime_type' => 'application/pdf',
            'size' => $this->faker->numberBetween(10_000, 1_000_000),
            'content_id' => Str::uuid()->toString(),
            'disposition' => 'attachment',
            'checksum' => hash('sha256', $filename.$this->faker->uuid),
            'metadata' => [
                'description' => $this->faker->sentence,
            ],
        ];
    }

    public function configure(): self
    {
        return $this->afterMaking(function (EmailAttachment $attachment): void {
            if ($attachment->message && method_exists($attachment->message, 'mailbox')) {
                $mailbox = $attachment->message->mailbox;
                if ($mailbox instanceof EmailMailbox) {
                    $attachment->tenant_id ??= $mailbox->tenant_id;
                    $attachment->brand_id ??= $mailbox->brand_id;
                    $attachment->mailbox_id ??= $mailbox->id;
                }
            }
        })->afterCreating(function (EmailAttachment $attachment): void {
            if (! $attachment->relationLoaded('message')) {
                $attachment->setRelation('message', $attachment->message()->first());
            }

            if ($attachment->message && method_exists($attachment->message, 'mailbox')) {
                $mailbox = $attachment->message->mailbox;
                if ($mailbox instanceof EmailMailbox) {
                    $attachment->tenant_id ??= $mailbox->tenant_id;
                    $attachment->brand_id ??= $mailbox->brand_id;
                    $attachment->mailbox_id ??= $mailbox->id;
                    if ($attachment->isDirty(['tenant_id', 'brand_id', 'mailbox_id'])) {
                        $attachment->save();
                    }
                }
            }
        });
    }
}
