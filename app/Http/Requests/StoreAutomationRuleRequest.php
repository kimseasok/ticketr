<?php

namespace App\Http\Requests;

use App\Modules\Helpdesk\Models\SlaPolicy;
use App\Modules\Helpdesk\Models\TicketTag;
use App\Support\Tenancy\TenantContext;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAutomationRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = app(TenantContext::class)->getTenantId();

        $conditionFields = ['priority', 'status', 'channel', 'brand_id', 'sla_policy_id'];
        $operators = ['equals', 'not_equals', 'in', 'not_in', 'contains'];
        $actionTypes = ['set_priority', 'set_status', 'assign_agent', 'apply_sla', 'add_tags'];

        return [
            'name' => ['required', 'string', 'max:150'],
            'slug' => [
                'nullable',
                'string',
                'max:150',
                Rule::unique('automation_rules', 'slug')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'event' => ['required', Rule::in(['ticket.created', 'ticket.updated', 'sla.breached'])],
            'match_type' => ['required', Rule::in(['all', 'any'])],
            'is_active' => ['sometimes', 'boolean'],
            'run_order' => ['sometimes', 'integer', 'min:0', 'max:65535'],
            'conditions' => ['nullable', 'array'],
            'conditions.*.field' => ['required_with:conditions', Rule::in($conditionFields)],
            'conditions.*.operator' => ['required_with:conditions', Rule::in($operators)],
            'conditions.*.value' => ['required_with:conditions'],
            'actions' => ['required', 'array', 'min:1'],
            'actions.*.type' => ['required', Rule::in($actionTypes)],
            'actions.*.value' => ['nullable'],
            'actions.*.user_id' => ['nullable', 'integer', 'exists:users,id'],
            'actions.*.sla_policy_id' => ['nullable', 'integer', 'exists:sla_policies,id'],
            'actions.*.tag_ids' => ['nullable', 'array'],
            'actions.*.tag_ids.*' => ['integer', 'exists:ticket_tags,id'],
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

        if (! $this->filled('match_type')) {
            $this->merge(['match_type' => 'all']);
        }
    }

    public function withValidator(Validator $validator): void
    {
        $tenantId = app(TenantContext::class)->getTenantId();

        $validator->after(function (Validator $validator) use ($tenantId): void {
            $actions = $this->input('actions', []);

            foreach ($actions as $index => $action) {
                $type = $action['type'] ?? null;

                if ($type === 'set_priority' && ! in_array($action['value'] ?? null, ['low', 'normal', 'high', 'urgent'], true)) {
                    $validator->errors()->add("actions.{$index}.value", __('Select a valid priority value.'));
                }

                if ($type === 'set_status' && ! in_array($action['value'] ?? null, ['open', 'pending', 'resolved', 'closed', 'archived'], true)) {
                    $validator->errors()->add("actions.{$index}.value", __('Select a valid status value.'));
                }

                if ($type === 'assign_agent' && empty($action['user_id'])) {
                    $validator->errors()->add("actions.{$index}.user_id", __('An agent is required for assignment actions.'));
                }

                if ($type === 'apply_sla' && empty($action['sla_policy_id'])) {
                    $validator->errors()->add("actions.{$index}.sla_policy_id", __('Select an SLA policy to apply.'));
                }

                if ($type === 'add_tags' && empty($action['tag_ids'])) {
                    $validator->errors()->add("actions.{$index}.tag_ids", __('Provide at least one tag identifier.'));
                }

                if ($tenantId) {
                    if (! empty($action['sla_policy_id'])) {
                        $policyExists = SlaPolicy::query()
                            ->where('tenant_id', $tenantId)
                            ->where('id', (int) $action['sla_policy_id'])
                            ->exists();

                        if (! $policyExists) {
                            $validator->errors()->add("actions.{$index}.sla_policy_id", __('Policy must belong to your tenant.'));
                        }
                    }

                    if (! empty($action['tag_ids'])) {
                        $tagCount = TicketTag::query()
                            ->where('tenant_id', $tenantId)
                            ->whereIn('id', $action['tag_ids'])
                            ->count();

                        if ($tagCount !== count($action['tag_ids'])) {
                            $validator->errors()->add("actions.{$index}.tag_ids", __('All tags must belong to your tenant.'));
                        }
                    }
                }
            }
        });
    }
}
