<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Modules\RiskManagement\Models\RiskRegister;
use App\Modules\RiskManagement\Services\ScoringEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkbookParityTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->project = Project::create([
            'name' => 'Workbook Parity Test',
            'module_type' => 'iso_27001',
            'user_id' => $this->user->id,
        ]);
    }

    public function test_workbook_columns_are_not_altered(): void
    {
        $risk = RiskRegister::create([
            'project_id' => $this->project->id,
            'serial_no' => 'WP-001',
            'asset_process_service' => 'Workbook Test',
            'risk_owner' => 'Owner',
            'risk_calculation_date' => now(),
            'asset_value_bdt' => 100000,
            'category' => 'Financial',
            'department' => 'IT',
            'threats' => ['External Attack'],
            'threat_level_t' => 3,
            'vulnerabilities' => ['Weak Authentication'],
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
        ]);

        $this->assertEquals('WP-001', $risk->serial_no);
        $this->assertEquals('Workbook Test', $risk->asset_process_service);
        $this->assertEquals('Owner', $risk->risk_owner);
        $this->assertEquals(100000, $risk->asset_value_bdt);
        $this->assertEquals('Financial', $risk->category);
        $this->assertEquals('IT', $risk->department);
        $this->assertEquals(['External Attack'], $risk->threats);
        $this->assertEquals(3, $risk->threat_level_t);
        $this->assertEquals(['Weak Authentication'], $risk->vulnerabilities);
        $this->assertEquals(3, $risk->vulnerability_level_av);
        $this->assertEquals(3, $risk->impact_confidentiality);
        $this->assertEquals(3, $risk->impact_integrity);
        $this->assertEquals(3, $risk->impact_availability);
        $this->assertEquals('Firewall', $risk->existing_control);
        $this->assertEquals(6, $risk->tv_t_av);
        $this->assertEquals(3, $risk->likelihood_lh);
        $this->assertEquals(54, $risk->risk_rating_avtvlh);
        $this->assertEquals('Not Accepted', $risk->measurement);
        $this->assertEquals(2, $risk->residual_tv);
        $this->assertEquals(2, $risk->residual_lh);
        $this->assertEquals(4, $risk->residual_rating);
    }

    public function test_imported_values_are_preserved_separate_from_computed(): void
    {
        $importedTv = 6;
        $importedLikelihood = 3;
        $importedRating = 54;

        $risk = RiskRegister::withoutEvents(function () use ($importedTv, $importedLikelihood, $importedRating) {
            return RiskRegister::create([
                'project_id' => $this->project->id,
                'serial_no' => 'WP-002',
                'asset_process_service' => 'Computed Test',
                'risk_owner' => 'Owner',
                'risk_calculation_date' => now(),
                'asset_value_bdt' => 100000,
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
                'tv_t_av' => $importedTv,
                'likelihood_lh' => $importedLikelihood,
                'risk_rating_avtvlh' => $importedRating,
                'measurement' => 'Not Accepted',
                'residual_tv' => 2,
                'residual_lh' => 2,
                'residual_rating' => 4,
            ]);
        });

        $engine = new ScoringEngine();
        $computedTv = $engine->calculateTvScore(4, 4);
        $computedRating = $engine->calculateInherentScore(4, $computedTv, 3);

        $this->assertNotEquals($importedRating, $computedRating);

        $risk->update([
            'computed_tv' => $computedTv,
            'computed_risk_rating' => $computedRating,
        ]);

        $this->assertEquals($importedRating, $risk->fresh()->risk_rating_avtvlh);
        $this->assertNotEquals($importedRating, $risk->fresh()->computed_risk_rating);
        $this->assertEquals($computedRating, $risk->fresh()->computed_risk_rating);
    }

    public function test_existing_columns_still_writable_for_import(): void
    {
        $risk = new RiskRegister();

        $risk->project_id = $this->project->id;
        $risk->serial_no = 'WP-003';
        $risk->asset_process_service = 'Import Test';
        $risk->risk_owner = 'Import Owner';
        $risk->risk_calculation_date = now();
        $risk->asset_value_bdt = 250000;
        $risk->category = 'Operational';
        $risk->department = 'Finance';
        $risk->threats = ['Fraud'];
        $risk->threat_level_t = 4;
        $risk->vulnerabilities = ['Weak Controls'];
        $risk->vulnerability_level_av = 4;
        $risk->impact_confidentiality = 4;
        $risk->impact_integrity = 4;
        $risk->impact_availability = 4;
        $risk->existing_control = 'Manual Review';
        $risk->tv_t_av = 8;
        $risk->likelihood_lh = 4;
        $risk->risk_rating_avtvlh = 128;
        $risk->measurement = 'Mitigated';
        $risk->residual_tv = 3;
        $risk->residual_lh = 2;
        $risk->residual_rating = 6;
        $risk->save();

        $this->assertDatabaseHas('risk_registers', [
            'serial_no' => 'WP-003',
            'asset_process_service' => 'Import Test',
            'category' => 'Operational',
            'risk_rating_avtvlh' => 128,
            'measurement' => 'Mitigated',
        ]);
    }

    public function test_new_field_does_not_break_existing_queries(): void
    {
        $risks = RiskRegister::where('project_id', $this->project->id)->get();

        $this->assertCount(0, $risks);

        RiskRegister::create([
            'project_id' => $this->project->id,
            'serial_no' => 'WP-004',
            'asset_process_service' => 'Existing Query Test',
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

        $risks = RiskRegister::where('project_id', $this->project->id)->get();
        $this->assertCount(1, $risks);

        $risk = $risks->first();
        $this->assertNotNull($risk->lifecycle_status);
        $this->assertNotNull($risk->getAttribute('exposure_value'));
    }
}
