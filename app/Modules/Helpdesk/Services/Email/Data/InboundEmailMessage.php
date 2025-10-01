<?php

namespace App\Modules\Helpdesk\Services\Email\Data;

use Carbon\CarbonImmutable;

class InboundEmailMessage
{
    /**
     * @param array<int, array<string, mixed>> $attachments
     * @param array<string, mixed> $headers
     * @param array<string, mixed> $metadata
     * @param array<int, string> $to
     * @param array<int, string> $cc
     * @param array<int, string> $bcc
     */
    public function __construct(
        public readonly string $messageId,
        public readonly ?string $threadId,
        public readonly ?string $subject,
        public readonly string $fromEmail,
        public readonly ?string $fromName,
        public readonly array $to = [],
        public readonly array $cc = [],
        public readonly array $bcc = [],
        public readonly ?string $textBody = null,
        public readonly ?string $htmlBody = null,
        public readonly array $attachments = [],
        public readonly array $headers = [],
        public readonly ?CarbonImmutable $receivedAt = null,
        public readonly array $metadata = []
    ) {
    }
}
