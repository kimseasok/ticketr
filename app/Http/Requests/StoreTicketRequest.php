<?php

namespace App\Http\Requests;

use App\Modules\Helpdesk\Models\Ticket;
use App\Support\Tenancy\TenantContext;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class StoreTicketRequest extends FormRequest
{
    public function rules(): array
    {
        $tenantId = $this->resolveTenantId();

        $statusRule = $tenantId
            ? Rule::exists('ticket_statuses', 'slug')->where(fn ($query) => $query->where('tenant_id', $tenantId))
            : Rule::in([
                Ticket::STATUS_OPEN,
                Ticket::STATUS_PENDING,
                Ticket::STATUS_RESOLVED,
                Ticket::STATUS_CLOSED,
                Ticket::STATUS_ARCHIVED,
            ]);

        $priorityRule = $tenantId
            ? Rule::exists('ticket_priorities', 'slug')->where(fn ($query) => $query->where('tenant_id', $tenantId))
            : Rule::in(['low', 'normal', 'high', 'urgent']);

        return [
            'tenant_id' => ['nullable', 'integer', 'exists:tenants,id'],
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            'contact_id' => ['nullable', 'integer', 'exists:contacts,id'],
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'created_by' => ['nullable', 'integer', 'exists:users,id'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'watcher_ids' => ['sometimes', 'array'],
            'watcher_ids.*' => ['integer', 'exists:users,id'],
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', $statusRule],
            'priority' => ['required', $priorityRule],
            'channel' => ['nullable', 'in:email,web,chat,phone'],
            'reference' => ['nullable', 'string', 'max:50'],
            'metadata' => ['nullable', 'array'],
            'sla_policy_id' => ['nullable', 'integer', 'exists:sla_policies,id'],
            'first_response_due_at' => ['nullable', 'date'],
            'resolution_due_at' => ['nullable', 'date'],
            'category_ids' => ['sometimes', 'array'],
            'category_ids.*' => ['integer', 'exists:ticket_categories,id'],
            'tag_ids' => ['sometimes', 'array'],
            'tag_ids.*' => ['integer', 'exists:ticket_tags,id'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }

    private function resolveTenantId(): ?int
    {
        $tenantId = $this->input('tenant_id');

        if ($tenantId !== null) {
            return (int) $tenantId;
        }

        return app(TenantContext::class)->getTenantId();
    }
}
