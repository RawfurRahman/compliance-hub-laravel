<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Modules\RiskManagement\Models\RiskRegister;
use App\Modules\RiskManagement\Models\RiskAcceptance;
use App\Modules\RiskManagement\Services\RiskRegisterService;
use App\Modules\RiskManagement\Services\RiskCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class RiskLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Project $project;
    protected RiskRegister $risk;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->project = Project::create([
            'name' => 'Lifecycle Test Project',
            'module_type' => 'iso_27001',
            'user_id' => $this->user->id,
        ]);

        $this->risk = RiskRegister::create([
            'project_id' => $this->project->id,
            'serial_no' => 'LC-001',
            'asset_process_service' => 'Test Risk',
            'risk_owner' => 'Test Owner',
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
            'lifecycle_status' => 'draft',
        ]);
    }

    public function test_draft_is_default_lifecycle(): void
    {
        $risk = RiskRegister::withoutEvents(function () {
            return RiskRegister::create([
                'project_id' => $this->project->id,
                'serial_no' => 'LC-002',
                'asset_process_service' => 'Default Test',
                'risk_owner' => 'Owner',
                'risk_calculation_date' => now(),
                'asset_value_bdt' => 50000,
                'category' => 'Test',
                'department' => 'IT',
                'threats' => ['Threat'],
                'threat_level_t' => 2,
                'vulnerabilities' => ['Vuln'],
                'vulnerability_level_av' => 2,
                'impact_confidentiality' => 2,
                'impact_integrity' => 2,
                'impact_availability' => 2,
                'existing_control' => 'None',
                'tv_t_av' => 4,
                'likelihood_lh' => 2,
                'risk_rating_avtvlh' => 16,
                'measurement' => 'Not Accepted',
                'residual_tv' => 1,
                'residual_lh' => 1,
                'residual_rating' => 1,
            ]);
        });

        $this->assertEquals('draft', $risk->fresh()->lifecycle_status);
    }

    public function test_can_transition_from_draft_to_assessed(): void
    {
        $this->actingAs($this->user);
        $service = new RiskRegisterService(new RiskCalculationService());

        $risk = $service->transitionLifecycle($this->risk, 'assessed');

        $this->assertEquals('assessed', $risk->lifecycle_status);
    }

    public function test_cannot_transition_from_draft_to_closed_directly(): void
    {
        $this->actingAs($this->user);
        $service = new RiskRegisterService(new RiskCalculationService());

        $this->expectException(\InvalidArgumentException::class);

        $service->transitionLifecycle($this->risk, 'closed');
    }

    public function test_cannot_transition_from_draft_to_expired_directly(): void
    {
        $this->actingAs($this->user);
        $service = new RiskRegisterService(new RiskCalculationService());

        $this->expectException(\InvalidArgumentException::class);

        $service->transitionLifecycle($this->risk, 'expired');
    }

    public function test_lifecycle_transition_creates_activity_log(): void
    {
        $this->actingAs($this->user);
        $service = new RiskRegisterService(new RiskCalculationService());

        $service->transitionLifecycle($this->risk, 'assessed');

        $this->assertDatabaseHas('activity_log', [
            'action' => 'risk_lifecycle_changed',
        ]);
    }

    public function test_can_transition_full_lifecycle(): void
    {
        $this->actingAs($this->user);
        $service = new RiskRegisterService(new RiskCalculationService());

        $service->transitionLifecycle($this->risk, 'assessed');
        $this->assertEquals('assessed', $this->risk->fresh()->lifecycle_status);

        $this->risk->update(['measurement' => 'Accepted']);
        RiskAcceptance::create([
            'risk_register_id' => $this->risk->id,
            'requested_by' => $this->user->id,
            'approved_by' => $this->user->id,
            'justification' => 'Test acceptance',
            'status' => 'Approved',
            'expiry_date' => now()->addYear(),
        ]);

        $service->transitionLifecycle($this->risk->fresh(), 'accepted');
        $this->assertEquals('accepted', $this->risk->fresh()->lifecycle_status);

        $service->transitionLifecycle($this->risk->fresh(), 'treated');
        $this->assertEquals('treated', $this->risk->fresh()->lifecycle_status);

        $service->transitionLifecycle($this->risk->fresh(), 'monitoring');
        $this->assertEquals('monitoring', $this->risk->fresh()->lifecycle_status);

        $service->transitionLifecycle($this->risk->fresh(), 'closed');
        $this->assertEquals('closed', $this->risk->fresh()->lifecycle_status);
    }

    public function test_lifecycle_can_escalate(): void
    {
        $this->actingAs($this->user);
        $service = new RiskRegisterService(new RiskCalculationService());

        $service->transitionLifecycle($this->risk, 'assessed');
        $service->transitionLifecycle($this->risk->fresh(), 'treated');
        $service->transitionLifecycle($this->risk->fresh(), 'monitoring');
        $service->transitionLifecycle($this->risk->fresh(), 'escalated');

        $this->assertEquals('escalated', $this->risk->fresh()->lifecycle_status);
    }

    public function test_exposure_value_is_set_on_risk(): void
    {
        $risk = $this->risk->fresh();
        $risk->update([
            'computed_tv' => 6,
            'computed_risk_rating' => 54,
            'exposure_value' => 5000.00,
        ]);

        $this->assertNotNull($risk->exposure_value);
        $this->assertEquals(5000.00, (float) $risk->exposure_value);
    }

    public function test_delta_is_correct(): void
    {
        $risk = $this->risk->fresh();
        $this->risk->update([
            'computed_risk_rating' => 100,
            'computed_residual_rating' => 30,
        ]);

        $this->assertEquals(70, $risk->fresh()->delta);
    }

    public function test_delta_is_zero_when_residual_exceeds_inherent(): void
    {
        $this->risk->update([
            'computed_risk_rating' => 30,
            'computed_residual_rating' => 50,
        ]);

        $this->assertEquals(0, $this->risk->fresh()->delta);
    }
}
