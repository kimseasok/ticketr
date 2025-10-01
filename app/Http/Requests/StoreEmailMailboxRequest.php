<?php

namespace App\Http\Requests;

use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmailMailboxRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && ($user->hasRole('Admin') || $user->can('email-mailboxes.manage'));
    }

    public function rules(): array
    {
        $tenantId = app(TenantContext::class)->getTenantId();

        $slugRule = Rule::unique('email_mailboxes', 'slug');

        if ($tenantId !== null) {
            $slugRule->where('tenant_id', $tenantId);
        }

        $brandRule = Rule::exists('brands', 'id');

        if ($tenantId !== null) {
            $brandRule->where('tenant_id', $tenantId);
        }

        return [
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['required', 'string', 'max:150', $slugRule],
            'direction' => ['required', Rule::in(['inbound', 'outbound', 'bidirectional'])],
            'protocol' => ['required', Rule::in(['imap', 'smtp'])],
            'host' => ['required', 'string', 'max:191'],
            'port' => ['required', 'integer', 'between:1,65535'],
            'encryption' => ['nullable', Rule::in(['ssl', 'tls', 'starttls', 'none'])],
            'username' => ['required', 'string', 'max:191'],
            'credentials' => ['required', 'array'],
            'credentials.password' => ['required', 'string'],
            'credentials.client_secret' => ['sometimes', 'string'],
            'settings' => ['nullable', 'array'],
            'settings.folder' => ['sometimes', 'string', 'max:120'],
            'settings.mailer' => ['sometimes', 'string', 'max:120'],
            'brand_id' => ['nullable', $brandRule],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
