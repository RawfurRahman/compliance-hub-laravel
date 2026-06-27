<?php

namespace Tests\Feature\Compliance;

use App\Models\Control;
use App\Models\Framework;
use App\Models\FrameworkControl;
use App\Models\Project;
use App\Models\User;
use App\Modules\Compliance\Models\ComplianceTest;
use App\Modules\Compliance\Models\ControlMonitor;
use App\Modules\Compliance\Models\MonitoringRule;
use App\Modules\Compliance\Services\ControlMonitorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ControlMonitorComplianceIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Control $control;
    protected MonitoringRule $monitoringRule;
    protected ControlMonitor $controlMonitor;
    protected ComplianceTest $complianceTest;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();

        $framework = Framework::create(['name' => 'PCI DSS', 'slug' => 'pci_dss', 'is_active' => true]);
        FrameworkControl::create([
            'framework_id' => $framework->id,
            'control_id' => 'PCI-1.1',
            'domain' => 'General',
            'requirement_description' => 'Test requirement',
        ]);

        $this->control = Control::create([
            'control_code' => 'CTRL-001',
            'name' => 'Access Control Policy',
            'is_active' => true,
        ]);

        $this->monitoringRule = MonitoringRule::create([
            'control_id' => $this->control->id,
            'name' => 'Access Control Test',
            'description' => 'Tests access control compliance',
            'rule_type' => 'manual',
            'check_expression' => null,
            'schedule_cron' => '0 * * * *',
            'threshold_value' => 80,
            'severity' => 'high',
            'is_active' => true,
            'created_by' => $this->user->id,
        ]);

        $this->controlMonitor = ControlMonitor::create([
            'control_id' => $this->control->id,
            'monitoring_rule_id' => $this->monitoringRule->id,
            'last_run_at' => now(),
            'next_run_at' => now()->subHour(),
            'status' => 'active',
            'last_result' => 'pass',
            'consecutive_failures' => 0,
        ]);

        $project = Project::create([
            'name' => 'Test Project',
            'module_type' => 'pci_dss',
            'user_id' => $this->user->id,
        ]);

        $this->complianceTest = ComplianceTest::create([
            'name' => 'Access Control Test',
            'description' => 'Tests access control compliance',
            'owner_user_id' => $this->user->id,
            'team' => 'Security Team',
            'test_type' => 'Automated',
            'sla_days' => 30,
            'status' => 'Passing',
            'last_run_at' => now(),
            'next_due_at' => now()->addDays(30),
            'control_monitor_id' => $this->controlMonitor->id,
        ]);
    }

    public function test_monitoring_check_failure_updates_linked_compliance_test_status_and_creates_failure_record(): void
    {
        // Arrange: Set up monitoring rule to fail for this test
        $this->monitoringRule->update(['check_expression' => 'false']);

        $service = app(ControlMonitorService::class);

        // Act: Run the monitoring check
        $finding = $service->runCheck($this->controlMonitor);

        // Assert: The ComplianceTest status should be updated correctly
        $this->complianceTest->refresh();
        $this->assertEquals('Needs Remediation', $this->complianceTest->status);

        // Assert: The next_due_at should have been calculated
        $this->assertNotNull($this->complianceTest->next_due_at);

        // Assert: A failure record should have been created with resource description
        $failure = $this->complianceTest->failures()->first();
        $this->assertNotNull($failure);
        $this->assertStringContainsString($this->control->control_code, $failure->failing_entity_description);
        $this->assertStringContainsString($finding->observation, $failure->failing_entity_description);
        $this->assertNull($failure->resolved_at);
    }

    public function test_monitoring_check_pass_updates_linked_compliance_test_status_and_resolves_failure(): void
    {
        // Arrange: Create a failure record first by running a failed check
        $this->monitoringRule->update(['check_expression' => 'false']);
        $service = app(ControlMonitorService::class);
        $service->runCheck($this->controlMonitor);

        $this->complianceTest->refresh();
        $this->assertEquals('Needs Remediation', $this->complianceTest->status);

        // Arrange: Reset monitoring rule to pass
        $this->monitoringRule->update(['check_expression' => 'true']);

        // Act: Run the monitoring check again
        $finding = $service->runCheck($this->controlMonitor);

        // Assert: The ComplianceTest status should be updated to Passing
        $this->complianceTest->refresh();
        $this->assertEquals('Passing', $this->complianceTest->status);

        // Assert: The failure record should have resolved_at set
        $failure = $this->complianceTest->failures()->first();
        $this->assertNotNull($failure);
        $this->assertNotNull($failure->resolved_at);
    }

    public function test_runAllDue_cascades_updates_to_all_linked_tests(): void
    {
        // Arrange: Create another ComplianceTest linked to the same ControlMonitor
        $project2 = Project::create([
            'name' => 'Test Project 2',
            'module_type' => 'pci_dss',
            'user_id' => $this->user->id,
        ]);

        $complianceTest2 = ComplianceTest::create([
            'name' => 'Access Control Test 2',
            'description' => 'Tests access control compliance v2',
            'owner_user_id' => $this->user->id,
            'team' => 'Security Team',
            'test_type' => 'Automated',
            'sla_days' => 30,
            'status' => 'Passing',
            'last_run_at' => now(),
            'next_due_at' => now()->addDays(30),
            'control_monitor_id' => $this->controlMonitor->id,
        ]);

        // Set up monitoring rule to fail
        $this->monitoringRule->update(['check_expression' => 'false']);

        $service = app(ControlMonitorService::class);

        // Act: Run all due monitoring checks
        $count = $service->runAllDue();

        // Assert: Both ComplianceTests should have been updated
        $this->complianceTest->refresh();
        $complianceTest2->refresh();

        $this->assertEquals('Needs Remediation', $this->complianceTest->status);
        $this->assertEquals('Needs Remediation', $complianceTest2->status);

        // Assert: Both should have failure records
        $this->assertCount(1, $this->complianceTest->failures);
        $this->assertCount(1, $complianceTest2->failures);
    }

    public function test_overdue_calculation_updates_test_status(): void
    {
        // Arrange: Set up monitoring rule to fail and set next_due_at in the past
        $this->monitoringRule->update(['check_expression' => 'false']);

        $service = app(ControlMonitorService::class);
        $finding = $service->runCheck($this->controlMonitor);

        // Arrange: Set next_due_at to past date
        $this->complianceTest->update([
            'next_due_at' => now()->subDays(1),
            'sla_days' => 30,
        ]);

        // Act: Run the check again (should update the test status)
        $finding = $service->runCheck($this->controlMonitor);

        // Assert: The status should be Overdue since next_due_at is in the past
        $this->complianceTest->refresh();
        $this->assertEquals('Overdue', $this->complianceTest->status);
    }

    public function test_no_control_monitor_ignored(): void
    {
        // Arrange: Create a ComplianceTest without a control monitor link
        $project3 = Project::create([
            'name' => 'Test Project 3',
            'module_type' => 'pci_dss',
            'user_id' => $this->user->id,
        ]);

        $complianceTest3 = ComplianceTest::create([
            'name' => 'Standalone Test',
            'description' => 'No monitoring link',
            'owner_user_id' => $this->user->id,
            'team' => 'Security Team',
            'test_type' => 'Automated',
            'sla_days' => 30,
            'status' => 'Passing',
            'last_run_at' => now(),
            'next_due_at' => now()->addDays(30),
            'control_monitor_id' => null,
        ]);

        $this->monitoringRule->update(['check_expression' => 'false']);

        $service = app(ControlMonitorService::class);
        $finding = $service->runCheck($this->controlMonitor);

        // Assert: The standalone test should remain unchanged
        $complianceTest3->refresh();
        $this->assertEquals('Passing', $complianceTest3->status);

        // Assert: The originally linked test should be updated
        $this->complianceTest->refresh();
        $this->assertEquals('Needs Remediation', $this->complianceTest->status);
    }
}