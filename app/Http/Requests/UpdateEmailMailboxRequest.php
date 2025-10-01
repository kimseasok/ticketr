<?php

namespace App\Http\Requests;

use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmailMailboxRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && ($user->hasRole('Admin') || $user->can('email-mailboxes.manage'));
    }

    public function rules(): array
    {
        $tenantId = app(TenantContext::class)->getTenantId();
        $mailboxId = $this->route('mailbox')?->id;

        $slugRule = Rule::unique('email_mailboxes', 'slug');

        if ($tenantId !== null) {
            $slugRule->where('tenant_id', $tenantId);
        }

        if ($mailboxId) {
            $slugRule->ignore($mailboxId);
        }

        $brandRule = Rule::exists('brands', 'id');

        if ($tenantId !== null) {
            $brandRule->where('tenant_id', $tenantId);
        }

        return [
            'name' => ['sometimes', 'string', 'max:150'],
            'slug' => ['sometimes', 'string', 'max:150', $slugRule],
            'direction' => ['sometimes', Rule::in(['inbound', 'outbound', 'bidirectional'])],
            'protocol' => ['sometimes', Rule::in(['imap', 'smtp'])],
            'host' => ['sometimes', 'string', 'max:191'],
            'port' => ['sometimes', 'integer', 'between:1,65535'],
            'encryption' => ['sometimes', Rule::in(['ssl', 'tls', 'starttls', 'none'])],
            'username' => ['sometimes', 'string', 'max:191'],
            'credentials' => ['sometimes', 'array'],
            'credentials.password' => ['sometimes', 'string'],
            'credentials.client_secret' => ['sometimes', 'string'],
            'settings' => ['sometimes', 'array'],
            'settings.folder' => ['sometimes', 'string', 'max:120'],
            'settings.mailer' => ['sometimes', 'string', 'max:120'],
            'brand_id' => ['nullable', $brandRule],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
