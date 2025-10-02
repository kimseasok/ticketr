<?php

namespace Database\Factories;

use App\Modules\Helpdesk\Models\AutomationRule;
use App\Modules\Helpdesk\Models\AutomationRuleVersion;
use App\Modules\Helpdesk\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AutomationRuleVersion>
 */
class AutomationRuleVersionFactory extends Factory
{
    protected $model = AutomationRuleVersion::class;

    public function definition(): array
    {
        return [
            'automation_rule_id' => AutomationRule::factory(),
            'tenant_id' => Tenant::factory(),
            'version' => 1,
            'definition' => [
                'conditions' => [
                    [
                        'field' => 'status',
                        'operator' => 'equals',
                        'value' => 'open',
                    ],
                ],
                'actions' => [
                    [
                        'type' => 'assign_agent',
                        'user_id' => 1,
                    ],
                ],
            ],
        ];
    }
}
