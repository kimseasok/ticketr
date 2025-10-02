<?php

namespace App\Http\Requests;

use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSlaPolicyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = app(TenantContext::class)->getTenantId();

        return [
            'name' => ['required', 'string', 'max:150'],
            'slug' => [
                'nullable',
                'string',
                'max:150',
                Rule::unique('sla_policies', 'slug')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'description' => ['nullable', 'string'],
            'priority_scope' => ['nullable', Rule::in(['low', 'normal', 'high', 'urgent'])],
            'channel_scope' => ['nullable', Rule::in(['email', 'web', 'chat', 'phone'])],
            'first_response_minutes' => ['required', 'integer', 'min:5', 'max:10080'],
            'resolution_minutes' => ['required', 'integer', 'min:5', 'max:10080'],
            'grace_minutes' => ['nullable', 'integer', 'min:0', 'max:1440'],
            'alert_after_minutes' => ['nullable', 'integer', 'min:5', 'max:10080'],
            'is_active' => ['sometimes', 'boolean'],
            'escalation_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->input('slug') && $this->filled('name')) {
            $this->merge([
                'slug' => str($this->input('name'))->slug('-')->toString(),
            ]);
        }

        if (! $this->filled('alert_after_minutes')) {
            $this->merge(['alert_after_minutes' => 30]);
        }
    }
}
