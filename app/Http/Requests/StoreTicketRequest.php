<?php

namespace App\Http\Requests;

use App\Modules\Helpdesk\Models\Ticket;
use Illuminate\Foundation\Http\FormRequest;

class StoreTicketRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'tenant_id' => ['nullable', 'integer', 'exists:tenants,id'],
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            'contact_id' => ['nullable', 'integer', 'exists:contacts,id'],
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'created_by' => ['nullable', 'integer', 'exists:users,id'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'in:'.implode(',', [
                Ticket::STATUS_OPEN,
                Ticket::STATUS_PENDING,
                Ticket::STATUS_RESOLVED,
                Ticket::STATUS_CLOSED,
                Ticket::STATUS_ARCHIVED,
            ])],
            'priority' => ['required', 'in:low,normal,high,urgent'],
            'channel' => ['nullable', 'in:email,web,chat,phone'],
            'reference' => ['nullable', 'string', 'max:50'],
            'metadata' => ['nullable', 'array'],
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
}
