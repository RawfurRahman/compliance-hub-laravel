<?php

namespace Tests\Feature;

use App\Models\AssessmentFinding;
use App\Models\Framework;
use App\Models\FrameworkControl;
use App\Models\Observation;
use App\Models\Project;
use App\Models\ProjectAssessment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ObservationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function makeGapFinding(User $owner): array
    {
        $project = Project::create([
            'name' => 'Observation Project '.uniqid(),
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
            'observation' => 'Periodic access review evidence was not provided.',
            'gap_description' => 'Periodic access review evidence is missing.',
            'impact' => 'Unauthorized access may go undetected.',
            'recommendation' => 'Provide the latest approved access review record.',
            'is_compliant' => false,
            'is_applicable' => true,
        ]);

        return [$project, $finding];
    }

    public function test_auditor_can_raise_an_observation_from_a_gap_finding()
    {
        $owner = User::factory()->create();
        [$project, $finding] = $this->makeGapFinding($owner);

        $response = $this->actingAs($owner)->post("/gap-assessment/findings/{$finding->id}/observations", [
            'title' => 'Missing periodic access review',
            'owner_user_id' => $owner->id,
            'target_date' => now()->addDays(30)->toDateString(),
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('observation.status', 'Open');
        $response->assertJsonPath('observation.gap', 'Periodic access review evidence is missing.');

        $this->assertDatabaseHas('observations', [
            'project_id' => $project->id,
            'assessment_finding_id' => $finding->id,
            'title' => 'Missing periodic access review',
            'raised_by' => $owner->id,
        ]);
    }

    public function test_cannot_raise_observation_from_a_finding_outside_a_gap_assessment()
    {
        $owner = User::factory()->create();
        [$project, $finding] = $this->makeGapFinding($owner);

        // Move the finding to a Final-type assessment to simulate a non-Gap source.
        $finding->projectAssessment->update(['type' => 'Final']);

        $response = $this->actingAs($owner)->post("/gap-assessment/findings/{$finding->id}/observations", [
            'title' => 'Should be rejected',
        ]);

        $response->assertStatus(400);
        $this->assertDatabaseCount('observations', 0);
    }

    public function test_auditor_can_update_management_response_and_status()
    {
        $owner = User::factory()->create();
        [, $finding] = $this->makeGapFinding($owner);

        $observation = Observation::create([
            'project_id' => $finding->projectAssessment->project_id,
            'assessment_finding_id' => $finding->id,
            'title' => 'Missing periodic access review',
            'raised_by' => $owner->id,
            'status' => 'Open',
        ]);

        $response = $this->actingAs($owner)->put("/observations/{$observation->id}", [
            'management_response' => 'Access review will be completed by end of quarter.',
            'corrective_action' => 'Schedule quarterly access review.',
            'status' => 'In Progress',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('observation.status', 'In Progress');

        $observation->refresh();
        $this->assertEquals('Access review will be completed by end of quarter.', $observation->management_response);
        $this->assertEquals('In Progress', $observation->status);
    }

    public function test_rejects_invalid_status_transition_value()
    {
        $owner = User::factory()->create();
        [, $finding] = $this->makeGapFinding($owner);

        $observation = Observation::create([
            'project_id' => $finding->projectAssessment->project_id,
            'assessment_finding_id' => $finding->id,
            'title' => 'Missing periodic access review',
            'raised_by' => $owner->id,
            'status' => 'Open',
        ]);

        $response = $this->actingAs($owner)->putJson("/observations/{$observation->id}", [
            'status' => 'NotARealStatus',
        ]);

        $response->assertStatus(422);
    }

    public function test_sending_observation_to_final_assessment_clones_the_finding_and_links_it()
    {
        $owner = User::factory()->create();
        [$project, $finding] = $this->makeGapFinding($owner);

        $observation = Observation::create([
            'project_id' => $project->id,
            'assessment_finding_id' => $finding->id,
            'title' => 'Missing periodic access review',
            'raised_by' => $owner->id,
            'status' => 'Open',
        ]);

        $response = $this->actingAs($owner)->post("/observations/{$observation->id}/send-to-final-assessment");

        $response->assertStatus(200);
        $observation->refresh();

        $this->assertNotNull($observation->sent_to_final_assessment_at);
        $this->assertNotNull($observation->final_assessment_finding_id);

        $finalFinding = $observation->finalAssessmentFinding;
        $this->assertNotNull($finalFinding);
        $this->assertEquals('Final', $finalFinding->projectAssessment->type);
        $this->assertEquals($finding->gap_description, $finalFinding->gap_description);
        $this->assertEquals($finding->id, $finalFinding->cloned_from_finding_id);

        // Sending twice is rejected rather than silently duplicating the final finding.
        $response = $this->actingAs($owner)->post("/observations/{$observation->id}/send-to-final-assessment");
        $response->assertStatus(400);
    }

    public function test_observations_are_scoped_to_their_project()
    {
        $owner = User::factory()->create();
        [$projectA, $findingA] = $this->makeGapFinding($owner);
        [$projectB, $findingB] = $this->makeGapFinding($owner);

        Observation::create([
            'project_id' => $projectA->id,
            'assessment_finding_id' => $findingA->id,
            'title' => 'Project A observation',
            'raised_by' => $owner->id,
        ]);
        Observation::create([
            'project_id' => $projectB->id,
            'assessment_finding_id' => $findingB->id,
            'title' => 'Project B observation',
            'raised_by' => $owner->id,
        ]);

        $response = $this->actingAs($owner)->get("/projects/{$projectA->id}/observations");

        $response->assertStatus(200);
        $titles = collect($response->json('observations'))->pluck('title')->all();
        $this->assertEquals(['Project A observation'], $titles);
    }
}
