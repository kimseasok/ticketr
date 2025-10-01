<?php

namespace App\Modules\Helpdesk\Services\Email\Connectors;

use App\Modules\Helpdesk\Models\Attachment;
use App\Modules\Helpdesk\Models\EmailMailbox;
use App\Modules\Helpdesk\Models\EmailOutboundMessage;
use App\Modules\Helpdesk\Services\Email\Contracts\MailboxDeliverer;
use App\Modules\Helpdesk\Services\Email\Data\DeliveryResult;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class SmtpMailboxDeliverer implements MailboxDeliverer
{
    public function __construct(private readonly EmailMailbox $mailbox)
    {
    }

    public function deliver(EmailOutboundMessage $message): DeliveryResult
    {
        $mailer = $this->mailbox->settings['mailer'] ?? config('mail.default');
        $mailerInstance = Mail::mailer($mailer);

        $message->loadMissing('attachments.storedAttachment');

        try {
            $mailerInstance->send([], [], function (Message $mail) use ($message): void {
                $mail->subject($message->subject ?? 'Ticket Reply');

                if ($message->html_body) {
                    $mail->html($message->html_body);
                } else {
                    $mail->text($message->text_body ?? '');
                }

                foreach ($message->to_recipients as $recipient) {
                    $mail->to($recipient);
                }

                foreach ($message->cc_recipients ?? [] as $recipient) {
                    $mail->cc($recipient);
                }

                foreach ($message->bcc_recipients ?? [] as $recipient) {
                    $mail->bcc($recipient);
                }

                foreach ($message->attachments as $attachment) {
                    $stored = $attachment->storedAttachment;

                    if (! $stored instanceof Attachment) {
                        continue;
                    }

                    $mail->attachFromStorageDisk(
                        $stored->disk,
                        $stored->path,
                        $stored->filename,
                        ['mime' => $stored->mime_type]
                    );
                }
            });

            $failures = $mailerInstance->failures();

            if (! empty($failures)) {
                return new DeliveryResult(false, error: ['failures' => $failures]);
            }

            return new DeliveryResult(true, providerMessageId: Str::uuid()->toString());
        } catch (Throwable $exception) {
            return new DeliveryResult(false, error: ['message' => $exception->getMessage()]);
        }
    }
}
