<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Modules\RiskManagement\Models\RiskRegister;
use App\Modules\RiskManagement\Models\RiskSnapshot;
use App\Modules\RiskManagement\Services\RiskSnapshotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RiskSnapshotTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->project = Project::create([
            'name' => 'Snapshot Test Project',
            'module_type' => 'iso_27001',
            'user_id' => $this->user->id,
        ]);

        $risk = RiskRegister::create([
            'project_id' => $this->project->id,
            'serial_no' => 'SS-001',
            'asset_process_service' => 'Snapshot Test Risk',
            'risk_owner' => 'Owner',
            'risk_calculation_date' => now(),
            'asset_value_bdt' => 100000,
            'category' => 'Test',
            'department' => 'IT',
            'threats' => ['Threat'],
            'threat_level_t' => 3,
            'vulnerabilities' => ['Vuln'],
            'vulnerability_level_av' => 3,
            'impact_confidentiality' => 3,
            'impact_integrity' => 3,
            'impact_availability' => 3,
            'existing_control' => 'None',
            'tv_t_av' => 6,
            'likelihood_lh' => 3,
            'risk_rating_avtvlh' => 54,
            'measurement' => 'Not Accepted',
            'residual_tv' => 2,
            'residual_lh' => 2,
            'residual_rating' => 4,
        ]);

        RiskRegister::create([
            'project_id' => $this->project->id,
            'serial_no' => 'SS-002',
            'asset_process_service' => 'High Risk Test',
            'risk_owner' => 'Owner',
            'risk_calculation_date' => now(),
            'asset_value_bdt' => 500000,
            'category' => 'Test',
            'department' => 'IT',
            'threats' => ['Threat'],
            'threat_level_t' => 4,
            'vulnerabilities' => ['Vuln'],
            'vulnerability_level_av' => 4,
            'impact_confidentiality' => 4,
            'impact_integrity' => 4,
            'impact_availability' => 4,
            'existing_control' => 'None',
            'tv_t_av' => 8,
            'likelihood_lh' => 4,
            'risk_rating_avtvlh' => 128,
            'measurement' => 'Not Accepted',
            'residual_tv' => 3,
            'residual_lh' => 2,
            'residual_rating' => 6,
        ]);
    }

    public function test_can_take_snapshot(): void
    {
        $service = new RiskSnapshotService();

        $snapshot = $service->takeSnapshot($this->project->id);

        $this->assertDatabaseHas('risk_snapshots', [
            'id' => $snapshot->id,
            'project_id' => $this->project->id,
        ]);

        $this->assertGreaterThan(0, $snapshot->total_risks);
        $this->assertGreaterThanOrEqual(0, $snapshot->critical_count);
        $this->assertGreaterThanOrEqual(0, $snapshot->high_count);
    }

    public function test_snapshot_counts_correctly(): void
    {
        $service = new RiskSnapshotService();

        $snapshot = $service->takeSnapshot($this->project->id);

        $this->assertEquals(2, $snapshot->total_risks);

        $this->assertEquals(1, $snapshot->critical_count);
        $this->assertEquals(0, $snapshot->high_count);
        $this->assertEquals(1, $snapshot->medium_count);
        $this->assertEquals(0, $snapshot->low_count);
    }

    public function test_snapshot_contains_snapshot_data(): void
    {
        $service = new RiskSnapshotService();

        $snapshot = $service->takeSnapshot($this->project->id);

        $this->assertNotNull($snapshot->snapshot_data);
        $this->assertIsArray($snapshot->snapshot_data);
        $this->assertArrayHasKey('risk_count_by_status', $snapshot->snapshot_data);
    }

    public function test_can_get_latest_by_project(): void
    {
        $service = new RiskSnapshotService();

        $first = $service->takeSnapshot($this->project->id);
        $this->assertNotNull($first);

        $second = $service->takeSnapshot($this->project->id);
        $this->assertNotNull($second);

        $this->assertGreaterThan($first->id, $second->id);
    }
}
