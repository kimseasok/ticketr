<?php

namespace App\Http\Requests;

use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTicketMacroRequest extends FormRequest
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
                'required',
                'string',
                'max:150',
                Rule::unique('ticket_macros', 'slug')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'description' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'visibility' => ['required', Rule::in(['tenant', 'brand', 'private'])],
            'metadata' => ['nullable', 'array'],
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            'is_shared' => ['sometimes', 'boolean'],
        ];
    }
}
