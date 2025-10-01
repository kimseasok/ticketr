<?php

namespace App\Modules\Helpdesk\Services\Email\Contracts;

use App\Modules\Helpdesk\Models\EmailMailbox;
use App\Modules\Helpdesk\Services\Email\Data\InboundEmailMessage;

interface MailboxFetcher
{
    /**
     * @return InboundEmailMessage[]
     */
    public function fetch(EmailMailbox $mailbox): array;
}
