<?php

namespace App\Http\Requests;

use App\Modules\Helpdesk\Models\Ticket;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTicketRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'brand_id' => ['sometimes', 'nullable', 'integer', 'exists:brands,id'],
            'contact_id' => ['sometimes', 'nullable', 'integer', 'exists:contacts,id'],
            'company_id' => ['sometimes', 'nullable', 'integer', 'exists:companies,id'],
            'assigned_to' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'subject' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', 'in:'.implode(',', [
                Ticket::STATUS_OPEN,
                Ticket::STATUS_PENDING,
                Ticket::STATUS_RESOLVED,
                Ticket::STATUS_CLOSED,
                Ticket::STATUS_ARCHIVED,
            ])],
            'priority' => ['sometimes', 'in:low,normal,high,urgent'],
            'channel' => ['sometimes', 'in:email,web,chat,phone'],
            'reference' => ['sometimes', 'string', 'max:50'],
            'metadata' => ['sometimes', 'nullable', 'array'],
            'first_response_due_at' => ['sometimes', 'nullable', 'date'],
            'resolution_due_at' => ['sometimes', 'nullable', 'date'],
            'first_responded_at' => ['sometimes', 'nullable', 'date'],
            'resolved_at' => ['sometimes', 'nullable', 'date'],
            'closed_at' => ['sometimes', 'nullable', 'date'],
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
