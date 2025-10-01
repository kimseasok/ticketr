<?php

namespace App\Http\Requests;

use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTicketMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user) {
            return false;
        }

        if ($this->filled('email') && ! ($user->hasRole('Admin') || $user->can('email-pipeline.deliver'))) {
            return false;
        }

        return true;
    }

    public function rules(): array
    {
        $tenantId = app(TenantContext::class)->getTenantId();
        $mailboxRule = Rule::exists('email_mailboxes', 'id');

        if ($tenantId !== null) {
            $mailboxRule->where('tenant_id', $tenantId);
        }

        return [
            'body' => ['required', 'string'],
            'visibility' => ['sometimes', Rule::in(['public', 'internal'])],
            'channel' => ['sometimes', 'string', 'max:100'],
            'author_type' => ['sometimes', Rule::in(['user', 'contact', 'system'])],
            'author_id' => ['nullable', 'integer'],
            'external_id' => ['nullable', 'string', 'max:191'],
            'metadata' => ['nullable', 'array'],
            'posted_at' => ['nullable', 'date'],
            'participants' => ['sometimes', 'array'],
            'participants.*.participant_type' => ['sometimes', Rule::in(['user', 'contact', 'system'])],
            'participants.*.participant_id' => ['nullable', 'integer'],
            'participants.*.role' => ['sometimes', Rule::in(['requester', 'cc', 'agent', 'watcher'])],
            'participants.*.visibility' => ['sometimes', Rule::in(['internal', 'external'])],
            'participants.*.last_seen_at' => ['nullable', 'date'],
            'participants.*.last_typing_at' => ['nullable', 'date'],
            'participants.*.is_muted' => ['sometimes', 'boolean'],
            'participants.*.metadata' => ['nullable', 'array'],
            'attachments' => ['sometimes', 'array'],
            'attachments.*.disk' => ['required_with:attachments', 'string'],
            'attachments.*.path' => ['required_with:attachments', 'string'],
            'attachments.*.filename' => ['required_with:attachments', 'string'],
            'attachments.*.mime_type' => ['required_with:attachments', 'string'],
            'attachments.*.size' => ['required_with:attachments', 'integer', 'min:0'],
            'attachments.*.metadata' => ['nullable', 'array'],
            'email' => ['sometimes', 'array'],
            'email.subject' => ['sometimes', 'string', 'max:255'],
            'email.mailbox_id' => ['sometimes', $mailboxRule],
            'email.to' => ['required_with:email', 'array', 'min:1'],
            'email.to.*' => ['email'],
            'email.cc' => ['sometimes', 'array'],
            'email.cc.*' => ['email'],
            'email.bcc' => ['sometimes', 'array'],
            'email.bcc.*' => ['email'],
            'email.text_body' => ['sometimes', 'string'],
            'email.html_body' => ['sometimes', 'string'],
        ];
    }
}
