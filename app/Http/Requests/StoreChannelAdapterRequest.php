<?php

namespace App\Http\Requests;

use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreChannelAdapterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = app(TenantContext::class)->getTenantId();

        return [
            'name' => ['required', 'string', 'max:120'],
            'slug' => [
                'required',
                'string',
                'max:120',
                Rule::unique('channel_adapters', 'slug')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'channel' => ['required', Rule::in(['email', 'web', 'chat', 'phone'])],
            'provider' => ['required', 'string', 'max:120'],
            'configuration' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            'is_active' => ['sometimes', 'boolean'],
            'last_synced_at' => ['nullable', 'date'],
        ];
    }
}
