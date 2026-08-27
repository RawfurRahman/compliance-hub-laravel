<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Modules\RiskManagement\Models\RiskRegister;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RiskRegisterCorruptedDataTest extends TestCase
{
    use RefreshDatabase;

    private function makeProject(): array
    {
        $owner = User::factory()->create();
        $project = Project::create([
            'name' => 'Corrupted Data Project',
            'module_type' => 'iso_27001',
            'user_id' => $owner->id,
        ]);

        return [$owner, $project];
    }

    public function test_saving_a_risk_with_an_empty_string_score_no_longer_fails_validation()
    {
        [$owner, $project] = $this->makeProject();

        $risk = RiskRegister::create([
            'project_id' => $project->id,
            'serial_no' => '1',
            'asset_process_service' => 'Payment Gateway',
            'risk_owner' => 'Jane Auditor',
            'likelihood_lh' => 3,
            'residual_tv' => 2,
            'residual_lh' => 2,
        ]);

        // Simulate a historical record corrupted by a prior import: SQLite doesn't
        // enforce column types, so an integer column can end up holding ''.
        DB::table('risk_registers')->where('id', $risk->id)->update(['threat_level_t' => '']);

        // Re-submitting the form with the (blank) value the edit page would have
        // actually rendered for this corrupted record must not permanently fail.
        $response = $this->actingAs($owner)->put("/projects/{$project->id}/risk-register/{$risk->id}", [
            'asset_process_service' => 'Payment Gateway',
            'risk_owner' => 'Jane Auditor',
            'threat_score' => '',
            'likelihood_lh' => 3,
            'residual_tv' => 2,
            'residual_lh' => 2,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $risk->refresh();
        $this->assertIsInt($risk->threat_level_t);
        $this->assertGreaterThanOrEqual(1, $risk->threat_level_t);
        $this->assertLessThanOrEqual(5, $risk->threat_level_t);
    }

    public function test_saving_a_risk_with_an_oversized_name_no_longer_fails_validation()
    {
        [$owner, $project] = $this->makeProject();

        $risk = RiskRegister::create([
            'project_id' => $project->id,
            'serial_no' => '1',
            'asset_process_service' => 'Payment Gateway',
            'risk_owner' => 'Jane Auditor',
            'likelihood_lh' => 3,
            'residual_tv' => 2,
            'residual_lh' => 2,
        ]);

        $oversizedName = str_repeat('A', 400);

        $response = $this->actingAs($owner)->put("/projects/{$project->id}/risk-register/{$risk->id}", [
            'asset_process_service' => $oversizedName,
            'risk_owner' => 'Jane Auditor',
            'threat_score' => 3,
            'likelihood_lh' => 3,
            'residual_tv' => 2,
            'residual_lh' => 2,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $risk->refresh();
        $this->assertLessThanOrEqual(255, strlen($risk->asset_process_service));
        $this->assertStringStartsWith('AAAA', $risk->asset_process_service);
    }

    public function test_valid_scores_and_names_are_left_untouched()
    {
        [$owner, $project] = $this->makeProject();

        $risk = RiskRegister::create([
            'project_id' => $project->id,
            'serial_no' => '1',
            'asset_process_service' => 'Payment Gateway',
            'risk_owner' => 'Jane Auditor',
            'likelihood_lh' => 3,
            'residual_tv' => 2,
            'residual_lh' => 2,
        ]);

        $response = $this->actingAs($owner)->put("/projects/{$project->id}/risk-register/{$risk->id}", [
            'asset_process_service' => 'Payment Gateway v2',
            'risk_owner' => 'Jane Auditor',
            'threat_score' => 4,
            'likelihood_lh' => 3,
            'residual_tv' => 2,
            'residual_lh' => 2,
        ]);

        $response->assertSessionHasNoErrors();

        $risk->refresh();
        $this->assertEquals(4, $risk->threat_level_t);
        $this->assertEquals('Payment Gateway v2', $risk->asset_process_service);
    }
}
