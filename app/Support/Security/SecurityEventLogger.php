<?php

namespace App\Support\Security;

use App\Modules\Helpdesk\Models\AuditLog;
use App\Models\User;

class SecurityEventLogger
{
    public function log(User $actor, string $action, array $metadata = []): void
    {
        AuditLog::create([
            'tenant_id' => $actor->tenant_id,
            'brand_id' => $actor->brand_id,
            'user_id' => $actor->id,
            'action' => $action,
            'auditable_type' => User::class,
            'auditable_id' => $actor->id,
            'metadata' => $this->sanitizeMetadata($metadata),
        ]);
    }

    private function sanitizeMetadata(array $metadata): array
    {
        if (isset($metadata['secret'])) {
            $metadata['secret'] = 'REDACTED';
        }

        if (isset($metadata['recovery_codes'])) {
            $metadata['recovery_codes'] = array_fill(0, count($metadata['recovery_codes']), 'REDACTED');
        }

        return $metadata;
    }
}
