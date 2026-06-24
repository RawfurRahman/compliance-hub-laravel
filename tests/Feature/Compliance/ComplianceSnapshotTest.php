<?php

namespace Tests\Feature\Compliance;

use App\Models\AssessmentFinding;
use App\Models\Framework;
use App\Models\FrameworkControl;
use App\Models\Project;
use App\Models\ProjectAssessment;
use App\Models\User;
use App\Modules\Compliance\Models\ComplianceSnapshot;
use App\Modules\Compliance\Services\ComplianceSnapshotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComplianceSnapshotTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->project = Project::create(['name' => 'Snapshot Test', 'module_type' => 'compliance', 'user_id' => $this->user->id]);

        $framework = Framework::create(['name' => 'PCI DSS', 'slug' => 'pci_dss', 'is_active' => true]);
        $fc = FrameworkControl::create(['framework_id' => $framework->id, 'control_id' => 'PCI-1.1', 'domain' => 'General', 'requirement_description' => 'Test requirement']);

        $risk = \App\Modules\RiskManagement\Models\RiskRegister::create([
            'project_id' => $this->project->id,
            'serial_no' => 'SN-001',
            'asset_process_service' => 'Snapshot Risk',
            'risk_owner' => 'Test Owner',
            'risk_calculation_date' => '2026-06-01',
            'asset_value_bdt' => 100000,
            'threats' => ['Data Breach'],
            'threat_level_t' => 3,
            'vulnerabilities' => ['Weak Access Control'],
            'vulnerability_level_av' => 3,
            'impact_confidentiality' => 3,
            'impact_integrity' => 3,
            'impact_availability' => 3,
            'existing_control' => 'Firewall',
            'tv_t_av' => 6,
            'likelihood_lh' => 3,
            'risk_rating_avtvlh' => 54,
            'measurement' => 'Not Accepted',
            'residual_tv' => 2,
            'residual_lh' => 2,
            'residual_rating' => 4,
            'category' => 'Cybersecurity',
            'department' => 'IT',
            'lifecycle_status' => 'draft',
        ]);

        $assessment = ProjectAssessment::create([
            'project_id' => $this->project->id,
            'framework_id' => $framework->id,
            'type' => 'Gap',
            'start_date' => now(),
            'overall_status' => 'in_progress',
        ]);

        AssessmentFinding::create([
            'project_assessment_id' => $assessment->id,
            'framework_control_id' => $fc->id,
            'status' => 'Open',
            'observation' => 'Finding 1',
            'is_compliant' => false,
            'is_applicable' => true,
            'compliance_state' => 'non_compliant',
            'risk_register_id' => $risk->id,
        ]);
    }

    public function test_can_take_snapshot(): void
    {
        $service = app(ComplianceSnapshotService::class);
        $snapshot = $service->takeSnapshot($this->project->id, 'weekly');

        $this->assertDatabaseHas('comp_compliance_snapshots', ['id' => $snapshot->id]);
        $this->assertEquals('weekly', $snapshot->snapshot_type);
    }

    public function test_snapshot_counts_are_correct(): void
    {
        $service = app(ComplianceSnapshotService::class);
        $snapshot = $service->takeSnapshot($this->project->id);

        $this->assertGreaterThanOrEqual(1, $snapshot->total_controls);
    }

    public function test_snapshot_contains_snapshot_data(): void
    {
        $service = app(ComplianceSnapshotService::class);
        $snapshot = $service->takeSnapshot($this->project->id);

        $this->assertIsArray($snapshot->snapshot_data);
        $this->assertArrayHasKey('non_compliant', $snapshot->snapshot_data);
    }

    public function test_compare_two_snapshots(): void
    {
        $service = app(ComplianceSnapshotService::class);
        $a = $service->takeSnapshot($this->project->id, 'weekly');
        $b = $service->takeSnapshot($this->project->id, 'weekly');

        $comparison = $service->compare($a->id, $b->id);
        $this->assertArrayHasKey('snapshot_a', $comparison);
        $this->assertArrayHasKey('snapshot_b', $comparison);
        $this->assertArrayHasKey('deltas', $comparison);
    }

    public function test_get_trend(): void
    {
        $service = app(ComplianceSnapshotService::class);
        $service->takeSnapshot($this->project->id, 'weekly');
        $service->takeSnapshot($this->project->id, 'weekly');

        $trend = $service->getTrend($this->project->id, 'weekly');
        $this->assertCount(2, $trend);
    }

    public function test_project_scope(): void
    {
        $service = app(ComplianceSnapshotService::class);
        $service->takeSnapshot($this->project->id, 'ondemand');

        $snapshots = ComplianceSnapshot::forProject($this->project->id)->get();
        $this->assertCount(1, $snapshots);
    }
}
