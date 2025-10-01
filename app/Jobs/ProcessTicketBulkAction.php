<?php

namespace App\Jobs;

use App\Models\User;
use App\Modules\Helpdesk\Services\TicketBulkActionService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;

class ProcessTicketBulkAction implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @param  array<int, mixed>  $payload
     */
    public function __construct(
        public int $userId,
        public array $payload
    ) {
    }

    /**
     * @var array{processed:int, skipped:int, errors:array<int, array<string, mixed>>}|null
     */
    public ?array $result = null;

    /**
     * @return array{processed:int, skipped:int, errors:array<int, array<string, mixed>>}
     */
    public function handle(TicketBulkActionService $service, TenantContext $tenantContext): array
    {
        $user = User::query()->findOrFail($this->userId);
        Auth::setUser($user);
        $tenantContext->setTenantId($user->tenant_id);
        $tenantContext->setBrandId($user->brand_id);

        try {
            $ticketIds = $this->payload['ticket_ids'] ?? [];
            $actions = $this->payload['actions'] ?? [];

            if (! is_array($ticketIds) || ! is_array($actions)) {
                return [
                    'processed' => 0,
                    'skipped' => count($ticketIds),
                    'errors' => [
                        [
                            'reason' => 'invalid_payload',
                            'messages' => ['ticket_ids and actions must be arrays.'],
                        ],
                    ],
                ];
            }

            $this->result = $service->apply($user, $ticketIds, $actions);

            return $this->result;
        } finally {
            Auth::forgetGuards();
            $tenantContext->clear();
        }
    }
}
