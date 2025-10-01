<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PerformTicketBulkActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ticket_ids' => ['required', 'array', 'min:1'],
            'ticket_ids.*' => ['integer', 'distinct'],
            'actions' => ['required', 'array', 'min:1'],
            'actions.*.type' => ['required', 'string', Rule::in(['assign', 'status', 'sla'])],
            'actions.*.assignee_id' => ['nullable', 'integer'],
            'actions.*.status' => ['nullable', 'string', 'max:100'],
            'actions.*.resolution_due_at' => ['nullable', 'date'],
            'actions.*.first_response_due_at' => ['nullable', 'date'],
        ];
    }
}
