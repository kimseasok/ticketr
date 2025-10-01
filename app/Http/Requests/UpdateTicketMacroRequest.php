<?php

namespace App\Http\Requests;

use App\Modules\Helpdesk\Models\TicketMacro;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTicketMacroRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var TicketMacro $macro */
        $macro = $this->route('ticket_macro');
        $tenantId = app(TenantContext::class)->getTenantId();

        return [
            'name' => ['sometimes', 'string', 'max:150'],
            'slug' => [
                'sometimes',
                'string',
                'max:150',
                Rule::unique('ticket_macros', 'slug')
                    ->where(fn ($query) => $query->where('tenant_id', $tenantId))
                    ->ignore($macro?->id),
            ],
            'description' => ['sometimes', 'nullable', 'string', 'max:255'],
            'body' => ['sometimes', 'string'],
            'visibility' => ['sometimes', Rule::in(['tenant', 'brand', 'private'])],
            'metadata' => ['sometimes', 'nullable', 'array'],
            'brand_id' => ['sometimes', 'nullable', 'integer', 'exists:brands,id'],
            'is_shared' => ['sometimes', 'boolean'],
        ];
    }
}
