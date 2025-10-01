<?php

namespace App\Modules\Helpdesk\Services\Email;

use App\Modules\Helpdesk\Services\Email\Contracts\MailboxDeliverer;
use App\Modules\Helpdesk\Services\Email\Contracts\MailboxFetcher;
use Illuminate\Support\Arr;

class MailboxConnectorRegistry
{
    /** @var array<string, callable> */
    private array $fetchers = [];

    /** @var array<string, callable> */
    private array $deliverers = [];

    public function registerFetcher(string $protocol, callable $resolver): void
    {
        $this->fetchers[$this->normalizeKey($protocol)] = $resolver;
    }

    public function registerDeliverer(string $protocol, callable $resolver): void
    {
        $this->deliverers[$this->normalizeKey($protocol)] = $resolver;
    }

    public function resolveFetcher(string $protocol, mixed ...$context): ?MailboxFetcher
    {
        $resolver = Arr::get($this->fetchers, $this->normalizeKey($protocol));

        if (! $resolver) {
            return null;
        }

        $instance = $resolver(...$context);

        return $instance instanceof MailboxFetcher ? $instance : null;
    }

    public function resolveDeliverer(string $protocol, mixed ...$context): ?MailboxDeliverer
    {
        $resolver = Arr::get($this->deliverers, $this->normalizeKey($protocol));

        if (! $resolver) {
            return null;
        }

        $instance = $resolver(...$context);

        return $instance instanceof MailboxDeliverer ? $instance : null;
    }

    private function normalizeKey(string $protocol): string
    {
        return strtolower(trim($protocol));
    }
}
