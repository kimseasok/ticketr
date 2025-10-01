<?php

namespace App\Modules\Helpdesk\Services\Email\Contracts;

use App\Modules\Helpdesk\Models\EmailOutboundMessage;
use App\Modules\Helpdesk\Services\Email\Data\DeliveryResult;

interface MailboxDeliverer
{
    public function deliver(EmailOutboundMessage $message): DeliveryResult;
}
