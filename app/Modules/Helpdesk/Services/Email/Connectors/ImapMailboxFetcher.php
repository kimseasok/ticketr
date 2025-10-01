<?php

namespace App\Modules\Helpdesk\Services\Email\Connectors;

use App\Modules\Helpdesk\Models\EmailMailbox;
use App\Modules\Helpdesk\Services\Email\Contracts\MailboxFetcher;
use App\Modules\Helpdesk\Services\Email\Data\InboundEmailMessage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class ImapMailboxFetcher implements MailboxFetcher
{
    public function fetch(EmailMailbox $mailbox): array
    {
        if (! function_exists('imap_open')) {
            Log::channel('stack')->warning('email.imap.unavailable', [
                'mailbox_id' => $mailbox->id,
                'reason' => 'php-imap extension missing',
            ]);

            return [];
        }

        $credentials = $mailbox->credentials ?? [];
        $password = $credentials['password'] ?? null;

        if (! $password) {
            throw new RuntimeException('Mailbox credentials missing password.');
        }

        $mailboxString = sprintf('{%s:%d/imap%s}%s',
            $mailbox->host,
            $mailbox->port,
            $mailbox->encryption ? '/'.strtolower($mailbox->encryption) : '',
            $mailbox->settings['folder'] ?? 'INBOX'
        );

        $connection = imap_open($mailboxString, $mailbox->username, $password, 0, 1);

        if (! $connection) {
            Log::channel('stack')->error('email.imap.connection_failed', [
                'mailbox_id' => $mailbox->id,
                'error' => imap_last_error(),
            ]);

            return [];
        }

        $criteria = 'UNSEEN';
        $messages = [];

        $uids = imap_search($connection, $criteria, SE_UID) ?: [];

        foreach ($uids as $uid) {
            $headerInfo = imap_headerinfo($connection, imap_msgno($connection, (int) $uid));
            $body = imap_body($connection, (int) $uid, FT_PEEK);

            $messages[] = new InboundEmailMessage(
                messageId: $headerInfo->message_id ?? Str::uuid()->toString(),
                threadId: $headerInfo->in_reply_to ?? null,
                subject: $headerInfo->subject ?? null,
                fromEmail: $headerInfo->from[0]->mailbox.'@'.$headerInfo->from[0]->host,
                fromName: $headerInfo->from[0]->personal ?? null,
                to: $this->mapAddressList($headerInfo->to ?? []),
                cc: $this->mapAddressList($headerInfo->cc ?? []),
                bcc: $this->mapAddressList($headerInfo->bcc ?? []),
                textBody: $body,
                htmlBody: null,
                attachments: [],
                headers: [
                    'message-id' => $headerInfo->message_id ?? null,
                    'in-reply-to' => $headerInfo->in_reply_to ?? null,
                ]
            );
        }

        imap_close($connection);

        return $messages;
    }

    /**
     * @param array<int, \stdClass> $addresses
     * @return array<int, string>
     */
    private function mapAddressList(array $addresses): array
    {
        return array_map(function ($address) {
            $mailbox = $address->mailbox ?? '';
            $host = $address->host ?? '';

            return $mailbox && $host ? sprintf('%s@%s', $mailbox, $host) : '';
        }, $addresses);
    }
}
