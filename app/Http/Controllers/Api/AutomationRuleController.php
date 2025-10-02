<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAutomationRuleRequest;
use App\Http\Requests\UpdateAutomationRuleRequest;
use App\Http\Resources\AutomationRuleResource;
use App\Modules\Helpdesk\Models\AuditLog;
use App\Modules\Helpdesk\Models\AutomationRule;
use App\Modules\Helpdesk\Models\AutomationRuleVersion;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class AutomationRuleController extends Controller
{
    public function __construct(private readonly TenantContext $tenantContext)
    {
    }

    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', AutomationRule::class);

        $query = AutomationRule::query()->with('brand');

        if ($brandId = $this->tenantContext->getBrandId()) {
            $query->where(function ($query) use ($brandId): void {
                $query->whereNull('brand_id')->orWhere('brand_id', $brandId);
            });
        }

        $rules = $query->ordered()->paginate(perPage: 15);

        return AutomationRuleResource::collection($rules);
    }

    public function store(StoreAutomationRuleRequest $request): JsonResponse
    {
        Gate::authorize('create', AutomationRule::class);

        $payload = $this->sanitizeActions($request->validated());
        $this->enforceScope($payload);

        $rule = AutomationRule::create($payload);
        $this->recordVersion($rule);
        $this->recordAudit($rule, 'created');

        Log::channel('structured')->info('automation.rule.created', [
            'rule_id' => $rule->id,
            'tenant_id' => $rule->tenant_id,
            'brand_id' => $rule->brand_id,
            'event' => $rule->event,
        ]);

        return (new AutomationRuleResource($rule))->response()->setStatusCode(201);
    }

    public function show(AutomationRule $automationRule): AutomationRuleResource
    {
        Gate::authorize('view', $automationRule);

        $this->assertAccessible($automationRule);

        return new AutomationRuleResource($automationRule);
    }

    public function update(UpdateAutomationRuleRequest $request, AutomationRule $automationRule): AutomationRuleResource
    {
        Gate::authorize('update', $automationRule);
        $this->assertAccessible($automationRule);

        $payload = $this->sanitizeActions($request->validated());
        $this->enforceScope($payload, $automationRule);

        $original = $automationRule->getOriginal();

        $automationRule->update($payload);

        if ($automationRule->wasChanged(['event', 'match_type', 'conditions', 'actions'])) {
            $this->recordVersion($automationRule);
        }

        $this->recordAudit($automationRule, 'updated', $original);

        Log::channel('structured')->info('automation.rule.updated', [
            'rule_id' => $automationRule->id,
            'tenant_id' => $automationRule->tenant_id,
            'brand_id' => $automationRule->brand_id,
        ]);

        return new AutomationRuleResource($automationRule);
    }

    public function destroy(AutomationRule $automationRule): JsonResponse
    {
        Gate::authorize('delete', $automationRule);
        $this->assertAccessible($automationRule);

        $this->recordAudit($automationRule, 'deleted', $automationRule->getAttributes());
        $automationRule->delete();

        Log::channel('structured')->info('automation.rule.deleted', [
            'rule_id' => $automationRule->id,
            'tenant_id' => $automationRule->tenant_id,
        ]);

        return response()->json(status: 204);
    }

    private function enforceScope(array &$payload, ?AutomationRule $rule = null): void
    {
        $tenantId = $this->tenantContext->getTenantId();
        $brandId = $this->tenantContext->getBrandId();

        if ($tenantId !== null) {
            $payload['tenant_id'] = $tenantId;
        } elseif ($rule) {
            $payload['tenant_id'] = $rule->tenant_id;
        }

        if ($brandId !== null) {
            $targetBrand = Arr::get($payload, 'brand_id', $rule?->brand_id);
            if ($targetBrand !== null && (int) $targetBrand !== $brandId) {
                abort(403, __('Automation rules must belong to your active brand.'));
            }
            $payload['brand_id'] = $brandId;
        }
    }

    private function assertAccessible(AutomationRule $rule): void
    {
        $tenantId = $this->tenantContext->getTenantId();
        $brandId = $this->tenantContext->getBrandId();

        if ($tenantId !== null && $rule->tenant_id !== $tenantId) {
            abort(404);
        }

        if ($brandId !== null && $rule->brand_id !== null && $rule->brand_id !== $brandId) {
            abort(404);
        }
    }

    private function recordAudit(AutomationRule $rule, string $action, array $original = []): void
    {
        $newValues = $rule->auditPayload();
        $oldValues = $original ? Arr::only($original, array_keys($newValues)) : null;

        AuditLog::create([
            'tenant_id' => $rule->tenant_id,
            'brand_id' => $rule->brand_id,
            'user_id' => auth()->id(),
            'action' => "automation_rule.{$action}",
            'auditable_type' => AutomationRule::class,
            'auditable_id' => $rule->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'metadata' => [
                'event' => $rule->event,
            ],
        ]);
    }

    private function recordVersion(AutomationRule $rule): void
    {
        $nextVersion = (int) ($rule->versions()->max('version') ?? 0) + 1;

        AutomationRuleVersion::create([
            'automation_rule_id' => $rule->id,
            'tenant_id' => $rule->tenant_id,
            'version' => $nextVersion,
            'definition' => [
                'event' => $rule->event,
                'match_type' => $rule->match_type,
                'conditions' => $rule->conditions,
                'actions' => $rule->actions,
            ],
            'created_by' => auth()->id(),
            'published_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function sanitizeActions(array $payload): array
    {
        if (! isset($payload['actions'])) {
            return $payload;
        }

        $payload['actions'] = collect($payload['actions'])
            ->map(function (array $action) {
                return array_filter($action, static fn ($value) => $value !== null && $value !== '');
            })
            ->values()
            ->all();

        return $payload;
    }
}
