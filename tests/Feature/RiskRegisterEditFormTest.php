<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Modules\RiskManagement\Models\RiskRegister;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RiskRegisterEditFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_edit_page_renders_and_prefills_the_risks_actual_field_values()
    {
        $owner = User::factory()->create();
        $project = Project::create([
            'name' => 'Risk Edit Project',
            'module_type' => 'iso_27001',
            'user_id' => $owner->id,
        ]);

        $risk = RiskRegister::create([
            'project_id' => $project->id,
            'serial_no' => '1',
            'asset_process_service' => 'Customer Database Server',
            'risk_owner' => 'Jane Auditor',
            'department' => 'IT Operations',
            'threats' => ['Unpatched software'],
            'vulnerabilities' => ['No patch management process'],
            'existing_control' => 'Manual patching, ad-hoc',
            'threat_level_t' => 4,
            'vulnerability_level_av' => 3,
            'likelihood_lh' => 3,
            'risk_rating_avtvlh' => 36,
            'residual_tv' => 2,
            'residual_lh' => 2,
            'residual_rating' => 4,
            'proposed_control' => 'Automated patch management',
            'lifecycle_status' => 'assessed',
            'category' => 'Cybersecurity',
        ]);

        $response = $this->actingAs($owner)->get("/projects/{$project->id}/risk-register/{$risk->id}/edit");

        $response->assertStatus(200);
        // These are server-rendered via old('field', $risk->field), independent of
        // Alpine JS execution -- the page must show the risk's real data even if a
        // JS error elsewhere on the page prevents the interactive tabs from working.
        $response->assertSee('Customer Database Server', false);
        $response->assertSee('Jane Auditor', false);
        $response->assertSee('Manual patching, ad-hoc', false);
        $response->assertSee('Automated patch management', false);

        // The inline script must be syntactically valid JavaScript -- a PHP-style
        // "(bool)" cast previously left inside the postComment() JS handler broke
        // the whole Alpine component, which hid every tab (including Edit Details)
        // behind an x-show that never got evaluated.
        $response->assertDontSee('(bool)c.is_internal', false);
    }

    public function test_previously_unmapped_fields_now_persist_through_a_real_submission()
    {
        $owner = User::factory()->create();
        $project = Project::create([
            'name' => 'Risk Field Mapping Project',
            'module_type' => 'iso_27001',
            'user_id' => $owner->id,
        ]);
        $risk = RiskRegister::create([
            'project_id' => $project->id,
            'serial_no' => '1',
            'asset_process_service' => 'Payment Gateway',
            'risk_owner' => 'John Auditor',
            'risk_calculation_date' => now(),
            'threat_level_t' => 3,
            'vulnerability_level_av' => 3,
            'likelihood_lh' => 3,
            'residual_tv' => 2,
            'residual_lh' => 2,
        ]);

        // These field names previously had no controller alias and no matching
        // column (financial_exposure, target_date, communication_status) -- values
        // submitted under those names were silently dropped. The form now submits
        // the real column names directly.
        $response = $this->actingAs($owner)->put("/projects/{$project->id}/risk-register/{$risk->id}", [
            'asset_process_service' => 'Payment Gateway',
            'risk_owner' => 'John Auditor',
            'risk_calculation_date' => now()->toDateString(),
            'likelihood_lh' => 4,
            'residual_tv' => 2,
            'residual_lh' => 2,
            'existing_controls' => 'WAF and quarterly penetration testing',
            'asset_value_bdt' => 750000,
            'implementation_to' => '2026-12-01',
            'communication' => 'Communicated',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $risk->refresh();
        $this->assertEquals('WAF and quarterly penetration testing', $risk->existing_control);
        $this->assertEquals(750000, (int) $risk->asset_value_bdt);
        $this->assertEquals('2026-12-01', $risk->implementation_to->toDateString());
        $this->assertEquals('Communicated', $risk->communication);
    }

    public function test_edit_page_script_contains_no_php_style_casts_leaked_into_js()
    {
        $owner = User::factory()->create();
        $project = Project::create([
            'name' => 'Risk Edit Project 2',
            'module_type' => 'iso_27001',
            'user_id' => $owner->id,
        ]);
        $risk = RiskRegister::create([
            'project_id' => $project->id,
            'serial_no' => '1',
            'asset_process_service' => 'Test Asset',
        ]);

        $response = $this->actingAs($owner)->get("/projects/{$project->id}/risk-register/{$risk->id}/edit");
        $response->assertStatus(200);

        // A PHP cast like (bool)x, (int)x, (string)x inside the <script> block is a
        // JS syntax error that silently breaks the entire Alpine component.
        $this->assertDoesNotMatchRegularExpression(
            '/\(bool\)\s*\w/',
            $response->getContent(),
            'Found a PHP-style cast inside rendered output -- this is invalid JavaScript syntax.'
        );
    }
}
