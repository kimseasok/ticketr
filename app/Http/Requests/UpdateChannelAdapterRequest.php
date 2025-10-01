<?php

namespace App\Http\Requests;

use App\Modules\Helpdesk\Models\ChannelAdapter;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateChannelAdapterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var ChannelAdapter $adapter */
        $adapter = $this->route('channel_adapter');
        $tenantId = app(TenantContext::class)->getTenantId();

        return [
            'name' => ['sometimes', 'string', 'max:120'],
            'slug' => [
                'sometimes',
                'string',
                'max:120',
                Rule::unique('channel_adapters', 'slug')
                    ->where(fn ($query) => $query->where('tenant_id', $tenantId))
                    ->ignore($adapter?->id),
            ],
            'channel' => ['sometimes', Rule::in(['email', 'web', 'chat', 'phone'])],
            'provider' => ['sometimes', 'string', 'max:120'],
            'configuration' => ['sometimes', 'nullable', 'array'],
            'metadata' => ['sometimes', 'nullable', 'array'],
            'brand_id' => ['sometimes', 'nullable', 'integer', 'exists:brands,id'],
            'is_active' => ['sometimes', 'boolean'],
            'last_synced_at' => ['sometimes', 'nullable', 'date'],
        ];
    }
}
