<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AutomationSchemaTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @group TKT-AUT-DB-01
     */
    public function test_automation_tables_are_created(): void
    {
        $this->assertTrue(Schema::hasTable('automation_rules'));
        $this->assertTrue(Schema::hasTable('automation_rule_versions'));
        $this->assertTrue(Schema::hasTable('automation_rule_executions'));
        $this->assertTrue(Schema::hasTable('sla_policies'));
        $this->assertTrue(Schema::hasTable('sla_transitions'));

        $automationColumns = Schema::getColumnListing('automation_rules');
        $this->assertContains('conditions', $automationColumns);
        $this->assertContains('actions', $automationColumns);
        $this->assertContains('match_type', $automationColumns);
        $this->assertContains('run_order', $automationColumns);

        $ticketColumns = Schema::getColumnListing('tickets');
        $this->assertContains('sla_policy_id', $ticketColumns);
        $this->assertContains('sla_snapshot', $ticketColumns);
        $this->assertContains('next_sla_check_at', $ticketColumns);
    }
}
