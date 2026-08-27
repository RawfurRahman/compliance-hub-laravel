<?php

namespace Tests\Feature;

use App\Models\AssessmentFinding;
use App\Models\Framework;
use App\Models\FrameworkControl;
use App\Models\Observation;
use App\Models\Project;
use App\Models\ProjectAssessment;
use App\Models\User;
use App\Modules\RiskManagement\Models\RiskRegister;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ObservationRiskCreationTest extends TestCase
{
    use RefreshDatabase;

    private function makeObservation(User $owner): array
    {
        $project = Project::create([
            'name' => 'Risk Creation Project '.uniqid(),
            'module_type' => 'iso_27001',
            'user_id' => $owner->id,
        ]);

        $framework = Framework::firstOrCreate(
            ['slug' => 'iso_27001'],
            ['name' => 'ISO 27001', 'is_active' => true]
        );

        $control = FrameworkControl::create([
            'framework_id' => $framework->id,
            'control_id' => 'A.9.2',
            'domain' => 'Access Control',
            'requirement_description' => 'Periodic access review must be performed.',
        ]);

        $assessment = ProjectAssessment::create([
            'project_id' => $project->id,
            'framework_id' => $framework->id,
            'type' => 'Gap',
            'start_date' => now(),
            'end_date' => now()->addMonth(),
        ]);

        $finding = AssessmentFinding::create([
            'project_assessment_id' => $assessment->id,
            'framework_control_id' => $control->id,
            'status' => 'Open',
            'risk_rating' => 'High',
            'gap_description' => 'Periodic access review evidence is missing.',
            'is_compliant' => false,
            'is_applicable' => true,
        ]);

        $observation = Observation::create([
            'project_id' => $project->id,
            'assessment_finding_id' => $finding->id,
            'title' => 'Missing periodic access review',
            'gap' => 'Periodic access review evidence is missing.',
            'risk_impact' => 'Unauthorized access may go undetected.',
            'recommendation' => 'Provide the latest approved access review record.',
            'owner_user_id' => $owner->id,
            'raised_by' => $owner->id,
            'status' => 'Open',
        ]);

        return [$project, $observation];
    }

    public function test_auditor_can_create_a_risk_from_an_observation()
    {
        $owner = User::factory()->create();
        [$project, $observation] = $this->makeObservation($owner);

        $response = $this->actingAs($owner)->post("/observations/{$observation->id}/add-to-risk-register");

        $response->assertStatus(200);
        $response->assertJsonPath('risk.source', 'observation');

        $observation->refresh();
        $this->assertNotNull($observation->risk_register_id);

        $risk = RiskRegister::find($observation->risk_register_id);
        $this->assertEquals($project->id, $risk->project_id);
        $this->assertEquals($observation->assessment_finding_id, $risk->assessment_finding_id);
        $this->assertEquals($observation->id, $risk->observation_id);
        $this->assertEquals('Missing periodic access review', $risk->asset_process_service);
    }

    public function test_cannot_create_a_second_risk_for_the_same_observation()
    {
        $owner = User::factory()->create();
        [, $observation] = $this->makeObservation($owner);

        $this->actingAs($owner)->post("/observations/{$observation->id}/add-to-risk-register")
            ->assertStatus(200);

        $response = $this->actingAs($owner)->post("/observations/{$observation->id}/add-to-risk-register");

        $response->assertStatus(400);
        $this->assertEquals(1, RiskRegister::where('observation_id', $observation->id)->count());
    }
}
