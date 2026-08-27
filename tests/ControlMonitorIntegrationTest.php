<?php

namespace Tests;

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

class ControlMonitorIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Control $control;

    protected MonitoringRule $monitoringRule;

    protected ControlMonitor $controlMonitor;

    protected ComplianceTest $complianceTest;

    protected ControlMonitorService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        Framework::create(['name' => 'PCI DSS', 'slug' => 'pci_dss', 'is_active' => true]);
        FrameworkControl::create([
            'framework_id' => Framework::first()->id,
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
        ]);

        $this->service = app(ControlMonitorService::class);
    }

    public function test_control_monitor_can_be_created()
    {
        $this->assertDatabaseHas('comp_control_monitors', [
            'id' => $this->controlMonitor->id,
            'control_id' => $this->control->id,
            'status' => 'active',
        ]);
    }

    public function test_control_monitor_belongs_to_control()
    {
        $this->assertTrue($this->controlMonitor->control->is($this->control));
    }

    public function test_control_monitor_belongs_to_monitoring_rule()
    {
        $this->assertTrue($this->controlMonitor->monitoringRule->is($this->monitoringRule));
    }

    public function test_control_monitor_has_related_compliance_tests()
    {
        $this->complianceTest->control_monitor_id = $this->controlMonitor->id;
        $this->complianceTest->save();

        $this->assertTrue(
            $this->controlMonitor->fresh()->complianceTests->contains($this->complianceTest)
        );
    }

    public function test_monitoring_rule_can_check_due_monitors()
    {
        $dueMonitors = ControlMonitor::dueForCheck()->get();
        $this->assertTrue($dueMonitors->contains($this->controlMonitor));
    }

    public function test_monitor_with_future_next_run_is_not_due()
    {
        $futureMonitor = ControlMonitor::create([
            'control_id' => $this->control->id,
            'monitoring_rule_id' => $this->monitoringRule->id,
            'status' => 'active',
            'next_run_at' => now()->addDays(1),
        ]);

        $dueMonitors = ControlMonitor::dueForCheck()->get();
        $this->assertFalse($dueMonitors->contains($futureMonitor));
    }
}
