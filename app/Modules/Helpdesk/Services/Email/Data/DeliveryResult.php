<?php

namespace App\Modules\Helpdesk\Services\Email\Data;

class DeliveryResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $providerMessageId = null,
        public readonly array $meta = [],
        public readonly ?array $error = null
    ) {
    }
}
