<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IngestTicketMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string'],
            'channel' => ['required', 'string', 'max:100'],
            'visibility' => ['nullable', Rule::in(['public', 'internal'])],
            'author_type' => ['nullable', Rule::in(['user', 'contact', 'system'])],
            'author_id' => ['nullable', 'integer'],
            'external_id' => ['nullable', 'string', 'max:191'],
            'metadata' => ['nullable', 'array'],
            'posted_at' => ['nullable', 'date'],
            'participants' => ['nullable', 'array'],
            'participants.*.participant_type' => ['nullable', Rule::in(['user', 'contact', 'system'])],
            'participants.*.participant_id' => ['nullable', 'integer'],
            'participants.*.role' => ['nullable', Rule::in(['requester', 'cc', 'agent', 'watcher'])],
            'participants.*.visibility' => ['nullable', Rule::in(['internal', 'external'])],
            'participants.*.last_seen_at' => ['nullable', 'date'],
            'participants.*.last_typing_at' => ['nullable', 'date'],
            'participants.*.is_muted' => ['nullable', 'boolean'],
            'participants.*.metadata' => ['nullable', 'array'],
            'attachments' => ['nullable', 'array'],
            'attachments.*.disk' => ['required_with:attachments', 'string'],
            'attachments.*.path' => ['required_with:attachments', 'string'],
            'attachments.*.filename' => ['required_with:attachments', 'string'],
            'attachments.*.mime_type' => ['required_with:attachments', 'string'],
            'attachments.*.size' => ['required_with:attachments', 'integer', 'min:0'],
            'attachments.*.metadata' => ['nullable', 'array'],
        ];
    }
}
