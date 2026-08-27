<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Modules\RiskManagement\Models\RiskRegister;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class RiskManualCreationTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->project = Project::create([
            'name' => 'Manual Creation Project',
            'module_type' => 'iso_27001',
            'user_id' => $this->user->id,
        ]);
    }

    public function test_manual_creation_accepts_legacy_fields_without_serial_number()
    {
        $this->actingAs($this->user);
        Bus::fake();

        $response = $this->postJson(route('risk-register.store', $this->project), [
            'risk_name' => 'Manual risk entry',
            'risk_owner' => 'Jane Doe',
            'date_identified' => '2026-08-13',
            'likelihood' => 4,
            'impact' => 4,
            'residual_likelihood' => 2,
            'residual_impact' => 2,
            'threat_score' => 4,
            'confidentiality' => 4,
            'integrity' => 4,
            'availability' => 4,
            'existing_controls' => 'Locks and badges',
            'treatment_decision' => 'In Review',
            'status' => 'Not Started',
            'follow_up_notes' => 'Created without a serial number field.',
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $saved = RiskRegister::where('project_id', $this->project->id)->first();
        $this->assertNotNull($saved);
        $this->assertNotNull($saved->serial_no);
        $this->assertNotEmpty($saved->serial_no);
        $this->assertEquals('Manual risk entry', $saved->asset_process_service);
        $this->assertEquals(4, $saved->likelihood_lh);
        $this->assertEquals(2, $saved->residual_tv);
        $this->assertEquals(2, $saved->residual_lh);
    }

    public function test_manual_creation_by_modal_expects_json_response()
    {
        $this->actingAs($this->user);
        Bus::fake();

        $response = $this->postJson(route('risk-register.store', $this->project), [
            'risk_name' => 'Modal created risk',
            'risk_owner' => 'John Doe',
            'date_identified' => '2026-08-13',
            'likelihood' => 3,
            'impact' => 3,
            'residual_likelihood' => 1,
            'residual_impact' => 1,
        ]);

        $response->assertOk();
        $this->assertNotEmpty($response->json('risk.serial_no'));
    }

    public function test_manual_creation_still_requires_core_fields()
    {
        $this->actingAs($this->user);
        Bus::fake();

        $response = $this->postJson(route('risk-register.store', $this->project), [
            'risk_owner' => 'Jane Doe',
        ]);

        $response->assertStatus(422);
        $errors = $response->json('errors');
        $this->assertArrayHasKey('asset_process_service', $errors);
        $this->assertArrayNotHasKey('serial_no', $errors);
    }
}
