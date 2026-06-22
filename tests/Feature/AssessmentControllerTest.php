<?php

namespace Tests\Feature;

use App\Models\Framework;
use App\Models\FrameworkControl;
use App\Models\Project;
use App\Models\ProjectAssessment;
use App\Models\AssessmentFinding;
use App\Models\Evidence;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssessmentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $project;
    protected $framework;
    protected $control;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->framework = Framework::create([
            'name' => 'ISO 27001',
            'slug' => 'iso_27001',
            'version' => '2022',
            'is_active' => true,
        ]);

        $this->project = Project::create([
            'name' => 'ISO Assessment Project',
            'module_type' => 'iso_27001',
            'user_id' => $this->user->id,
        ]);

        $this->control = FrameworkControl::create([
            'framework_id' => $this->framework->id,
            'control_id' => 'A.5.1',
            'domain' => 'Policies for information security',
            'requirement_description' => 'Information security policies should be defined.',
        ]);
    }

    public function test_show_assessment_dashboard()
    {
        $this->actingAs($this->user);

        // Access dashboard without active assessment
        $response = $this->get(route('assessments.show', $this->project));
        $response->assertStatus(200);
        $response->assertViewIs('assessments.dashboard');
        $response->assertSee('No Gap Assessment Found');

        // Create assessment
        $assessment = ProjectAssessment::create([
            'project_id' => $this->project->id,
            'framework_id' => $this->framework->id,
            'type' => 'Gap',
            'start_date' => now(),
            'end_date' => now()->addMonth(),
        ]);

        // Access again
        $response = $this->get(route('assessments.show', $this->project));
        $response->assertStatus(200);
        $response->assertSee('ISO 27001 Assessment');
        $response->assertSee($this->project->name);
    }

    public function test_store_assessment_initializes_findings()
    {
        $this->actingAs($this->user);

        $response = $this->post(route('assessments.store', $this->project), [
            'assessment_type' => 'Gap',
            'start_date' => now()->format('Y-m-d'),
            'end_date' => now()->addMonth()->format('Y-m-d'),
        ]);

        $response->assertRedirect(route('assessments.show', [$this->project, 'type' => 'gap']));

        $assessment = ProjectAssessment::where('project_id', $this->project->id)
            ->where('framework_id', $this->framework->id)
            ->where('type', 'Gap')
            ->first();

        $this->assertNotNull($assessment);
        $this->assertEquals(1, $assessment->findings()->count());
    }

    public function test_clone_assessment_findings()
    {
        $this->actingAs($this->user);

        // Create Gap assessment
        $gap = ProjectAssessment::create([
            'project_id' => $this->project->id,
            'framework_id' => $this->framework->id,
            'type' => 'Gap',
            'start_date' => now(),
            'end_date' => now()->addMonth(),
        ]);

        $finding = $gap->findings()->create([
            'framework_control_id' => $this->control->id,
            'status' => 'Closed',
            'risk_rating' => 'None',
            'observation' => 'Test Observation',
            'is_compliant' => true,
        ]);

        $response = $this->post(route('assessments.clone', $this->project), [
            'source_id' => $gap->id,
            'start_date' => now()->format('Y-m-d'),
            'end_date' => now()->addMonth()->format('Y-m-d'),
        ]);

        $response->assertRedirect(route('assessments.show', [$this->project, 'type' => 'final']));

        $final = ProjectAssessment::where('project_id', $this->project->id)
            ->where('framework_id', $this->framework->id)
            ->where('type', 'Final')
            ->first();

        $this->assertNotNull($final);
        $this->assertDatabaseHas('assessment_findings', [
            'project_assessment_id' => $final->id,
            'framework_control_id' => $this->control->id,
            'cloned_from_finding_id' => $finding->id,
            'is_compliant' => true,
        ]);
    }

    public function test_findings_crud()
    {
        $this->actingAs($this->user);

        $assessment = ProjectAssessment::create([
            'project_id' => $this->project->id,
            'framework_id' => $this->framework->id,
            'type' => 'Gap',
            'start_date' => now(),
            'end_date' => now()->addMonth(),
        ]);

        // 1. Store Finding
        $response = $this->postJson(route('assessments.findings.store', $assessment), [
            'serial_no' => 'A.5.2',
            'status' => 'In Progress',
            'observation_title' => 'Missing policies check',
            'risk_rating' => 'Medium',
            'gap_description' => 'Need to define policies.',
            'is_compliant' => false,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);

        $finding = AssessmentFinding::where('project_assessment_id', $assessment->id)
            ->where('observation', 'like', '%Missing policies check%')
            ->first();

        $this->assertNotNull($finding);
        $this->assertEquals('A.5.2', $finding->serial_no);

        // 2. Update Finding
        $response = $this->putJson(route('assessments.findings.update', $finding), [
            'status' => 'Closed',
            'is_compliant' => true,
            'observation_title' => 'Updated Observation title',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('assessment_findings', [
            'id' => $finding->id,
            'status' => 'Closed',
            'is_compliant' => true,
            'observation' => 'Updated Observation title',
        ]);

        // 3. Destroy Finding
        $response = $this->deleteJson(route('assessments.findings.destroy', $finding));
        $response->assertStatus(200);
        $this->assertDatabaseMissing('assessment_findings', [
            'id' => $finding->id,
        ]);
    }

    public function test_generate_report_pdf()
    {
        $this->actingAs($this->user);

        $assessment = ProjectAssessment::create([
            'project_id' => $this->project->id,
            'framework_id' => $this->framework->id,
            'type' => 'Gap',
            'start_date' => now(),
            'end_date' => now()->addMonth(),
        ]);

        $response = $this->get(route('assessments.report', $assessment));
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_cannot_access_or_start_final_assessment_without_completed_gap_assessment()
    {
        $this->actingAs($this->user);

        // 1. Try to access Final show view when no Gap assessment exists -> redirect to gap
        $response = $this->get(route('assessments.show', [$this->project, 'type' => 'final']));
        $response->assertRedirect(route('assessments.show', [$this->project, 'type' => 'gap']));
        $response->assertSessionHas('error', 'Cannot start or access the Final Assessment (Phase 2) until the Gap Assessment (Phase 1) is 100% compliant.');

        // 2. Create an incomplete Gap assessment
        $gap = ProjectAssessment::create([
            'project_id' => $this->project->id,
            'framework_id' => $this->framework->id,
            'type' => 'Gap',
            'start_date' => now(),
            'end_date' => now()->addMonth(),
        ]);
        $gap->findings()->create([
            'framework_control_id' => $this->control->id,
            'status' => 'Open',
            'is_compliant' => false,
        ]);

        // 3. Try to access Final show view when Gap is incomplete -> redirect to gap
        $response = $this->get(route('assessments.show', [$this->project, 'type' => 'final']));
        $response->assertRedirect(route('assessments.show', [$this->project, 'type' => 'gap']));
        $response->assertSessionHas('error');

        // 4. Try to initialize Final store when Gap is incomplete -> redirect back with error
        $response = $this->post(route('assessments.store', $this->project), [
            'assessment_type' => 'Final',
            'start_date' => now()->format('Y-m-d'),
            'end_date' => now()->addMonth()->format('Y-m-d'),
        ]);
        $response->assertRedirect();
        $response->assertSessionHas('error');

        // 5. Try to clone when Gap is incomplete -> redirect back with error
        $response = $this->post(route('assessments.clone', $this->project), [
            'source_id' => $gap->id,
            'start_date' => now()->format('Y-m-d'),
            'end_date' => now()->addMonth()->format('Y-m-d'),
        ]);
        $response->assertRedirect();
        $response->assertSessionHas('error');
    }
}
