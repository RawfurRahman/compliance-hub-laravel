<?php

namespace Tests\Feature;

use App\Models\Framework;
use App\Models\FrameworkControl;
use App\Models\Project;
use App\Models\ProjectAssessment;
use App\Models\AssessmentFinding;
use App\Models\Evidence;
use App\Models\User;
use App\Services\AssessmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnifiedAssessmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_initialize_assessment()
    {
        // 1. Create a user, project, and framework
        $user = User::factory()->create();
        $project = Project::create([
            'name' => 'Test Project',
            'module_type' => 'iso_27001',
            'user_id' => $user->id
        ]);
        $framework = Framework::create([
            'name' => 'ISO 27001',
            'slug' => 'iso_27001',
            'version' => '2022',
            'is_active' => true,
        ]);

        // 2. Create framework controls
        $control1 = FrameworkControl::create([
            'framework_id' => $framework->id,
            'control_id' => 'A.5.1',
            'domain' => 'Policies',
            'requirement_description' => 'Information security policies should be defined.',
        ]);

        $control2 = FrameworkControl::create([
            'framework_id' => $framework->id,
            'control_id' => 'A.5.2',
            'domain' => 'Policies',
            'requirement_description' => 'Policies should be reviewed.',
        ]);

        // 3. Initialize assessment
        $assessment = ProjectAssessment::create([
            'project_id' => $project->id,
            'framework_id' => $framework->id,
            'type' => 'Gap',
            'start_date' => now(),
            'end_date' => now()->addMonth(),
        ]);

        app(AssessmentService::class)->initialize($assessment);

        // 4. Assert findings were created
        $this->assertEquals(2, $assessment->findings()->count());
        $this->assertDatabaseHas('assessment_findings', [
            'project_assessment_id' => $assessment->id,
            'framework_control_id' => $control1->id,
            'is_compliant' => false,
        ]);
    }

    public function test_gap_to_final_auto_cloning_and_uncloning()
    {
        // 1. Setup project, framework, controls
        $user = User::factory()->create();
        $project = Project::create(['name' => 'Agnostic Test', 'module_type' => 'iso_27001', 'user_id' => $user->id]);
        $framework = Framework::create(['name' => 'ISO 27001', 'slug' => 'iso_27001', 'is_active' => true]);
        
        $control = FrameworkControl::create([
            'framework_id' => $framework->id,
            'control_id' => 'A.8.1',
            'domain' => 'Assets',
            'requirement_description' => 'Inventory of assets.',
        ]);

        $gapAssessment = ProjectAssessment::create([
            'project_id' => $project->id,
            'framework_id' => $framework->id,
            'type' => 'Gap',
            'start_date' => now(),
            'end_date' => now()->addMonth(),
        ]);

        app(AssessmentService::class)->initialize($gapAssessment);

        $finding = $gapAssessment->findings()->first();

        // 2. Mark compliant
        $finding->update([
            'is_compliant' => true,
            'observation' => 'Asset inventory is in place.',
        ]);

        // 3. Assert Final assessment and cloned finding exist
        $finalAssessment = ProjectAssessment::where('project_id', $project->id)
            ->where('framework_id', $framework->id)
            ->where('type', 'Final')
            ->first();

        $this->assertNotNull($finalAssessment);
        $this->assertDatabaseHas('assessment_findings', [
            'project_assessment_id' => $finalAssessment->id,
            'cloned_from_finding_id' => $finding->id,
            'is_compliant' => true,
            'observation' => 'Asset inventory is in place.',
        ]);

        // 4. Mark non-compliant again
        $finding->update(['is_compliant' => false]);

        // 5. Assert cloned finding is deleted
        $this->assertDatabaseMissing('assessment_findings', [
            'project_assessment_id' => $finalAssessment->id,
            'cloned_from_finding_id' => $finding->id,
        ]);
    }

    public function test_evidence_attachment()
    {
        // Setup
        $user = User::factory()->create();
        $project = Project::create(['name' => 'Evidence Test', 'module_type' => 'iso_27001', 'user_id' => $user->id]);
        $framework = Framework::create(['name' => 'ISO 27001', 'slug' => 'iso_27001', 'is_active' => true]);
        
        $control = FrameworkControl::create([
            'framework_id' => $framework->id,
            'control_id' => 'A.5.1',
            'domain' => 'Policies',
            'requirement_description' => 'Define policies.',
        ]);

        $assessment = ProjectAssessment::create([
            'project_id' => $project->id,
            'framework_id' => $framework->id,
            'type' => 'Gap',
        ]);

        app(AssessmentService::class)->initialize($assessment);
        $finding = $assessment->findings()->first();

        $evidence = Evidence::create([
            'project_id' => $project->id,
            'requirement_id' => $control->id,
            'name' => 'policy.pdf',
            'path' => 'evidence/policy.pdf',
        ]);

        // Attach
        $finding->evidence()->attach($evidence->id);

        $this->assertTrue($finding->evidence()->where('evidence.id', $evidence->id)->exists());
    }

    public function test_excel_import_recovers_truncated_trailing_zeros()
    {
        $import = new \App\Imports\FrameworkControlImport(1);

        // Simulate reading sequential rows where 5.10 was formatted as 5.1, and 5.20 was formatted as 5.2
        $rows = [
            ['control_id' => '5.8', 'domain' => 'Policies', 'requirement_description' => 'Desc 8'],
            ['control_id' => '5.9', 'domain' => 'Policies', 'requirement_description' => 'Desc 9'],
            ['control_id' => '5.1', 'domain' => 'Policies', 'requirement_description' => 'Desc 10'], // Should be 5.10
            ['control_id' => '5.11', 'domain' => 'Policies', 'requirement_description' => 'Desc 11'],
            ['control_id' => '5.19', 'domain' => 'Policies', 'requirement_description' => 'Desc 19'],
            ['control_id' => '5.2', 'domain' => 'Policies', 'requirement_description' => 'Desc 20'], // Should be 5.20
            ['control_id' => '5.21', 'domain' => 'Policies', 'requirement_description' => 'Desc 21'],
        ];

        $results = [];
        foreach ($rows as $row) {
            $model = $import->model($row);
            if ($model) {
                $results[] = $model->control_id;
            }
        }

        $expected = ['5.8', '5.9', '5.10', '5.11', '5.19', '5.20', '5.21'];
        $this->assertEquals($expected, $results);
    }

    public function test_excel_import_matches_complex_slash_headers()
    {
        $import = new \App\Imports\FrameworkControlImport(1);

        $row = [
            'domain_domain_name' => 'Asset Management',
            'control_id_control_no' => 'A.5.1',
            'requirement_description_description' => 'Describe policy details.',
            'required_evidence_evidence' => 'Document policy list.'
        ];

        $model = $import->model($row);

        $this->assertNotNull($model);
        $this->assertEquals('A.5.1', $model->control_id);
        $this->assertEquals('Asset Management', $model->domain);
        $this->assertEquals('Describe policy details.', $model->requirement_description);
        $this->assertEquals('Document policy list.', $model->required_evidence);
    }

    public function test_evidence_workspace_loads_for_agnostic_project()
    {
        $user = User::factory()->create();
        $user->roles()->create(['name' => 'Auditor']);
        $project = Project::create([
            'name' => 'Agnostic Project',
            'module_type' => 'iso_27001',
            'user_id' => $user->id
        ]);
        $framework = Framework::create([
            'name' => 'ISO 27001',
            'slug' => 'iso_27001',
            'is_active' => true,
        ]);
        $control = FrameworkControl::create([
            'framework_id' => $framework->id,
            'control_id' => '5.1',
            'domain' => 'Organizational controls',
            'requirement_description' => 'Security policies',
        ]);

        $response = $this->actingAs($user)->get("/evidence/{$project->id}");

        $response->assertStatus(200);
        $response->assertViewHas('isPci', false);
        $response->assertViewHas('requirements');
        $requirements = $response->viewData('requirements');
        $this->assertCount(1, $requirements);
        $this->assertEquals('5.1', $requirements->first()['req_num']);
    }

    public function test_evidence_upload_for_agnostic_project()
    {
        \Illuminate\Support\Facades\Storage::fake('public');

        $user = User::factory()->create();
        $project = Project::create([
            'name' => 'Agnostic Upload Project',
            'module_type' => 'iso_27001',
            'user_id' => $user->id
        ]);
        $framework = Framework::create([
            'name' => 'ISO 27001',
            'slug' => 'iso_27001',
            'is_active' => true,
        ]);
        $control = FrameworkControl::create([
            'framework_id' => $framework->id,
            'control_id' => '5.2',
            'domain' => 'Organizational controls',
            'requirement_description' => 'Role definition',
        ]);

        $file = \Illuminate\Http\Testing\File::create('policy_doc.pdf', 100, 'application/pdf');

        $response = $this->actingAs($user)->post("/evidence/{$project->id}/upload", [
            'file' => $file,
            'requirement_id' => $control->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('evidence_files', [
            'project_id' => $project->id,
            'framework_control_id' => $control->id,
            'pci_dss_requirement_id' => null,
            'original_filename' => 'policy_doc.pdf',
        ]);
    }

    public function test_evidence_zip_export_for_agnostic_project()
    {
        \Illuminate\Support\Facades\Storage::fake('public');

        $user = User::factory()->create();
        $user->roles()->create(['name' => 'Auditor']);

        $project = Project::create([
            'name' => 'Agnostic ZIP Project',
            'module_type' => 'iso_27001',
            'user_id' => $user->id
        ]);
        $framework = Framework::create([
            'name' => 'ISO 27001',
            'slug' => 'iso_27001',
            'is_active' => true,
        ]);
        $control = FrameworkControl::create([
            'framework_id' => $framework->id,
            'control_id' => '5.3',
            'domain' => 'Organizational controls',
            'requirement_description' => 'Security review',
        ]);

        // Create accepted evidence file
        $filePath = 'evidence/' . $project->id . '/test_file.txt';
        \Illuminate\Support\Facades\Storage::disk('public')->put($filePath, 'plain text content');

        $evidenceFile = $project->evidenceFiles()->create([
            'framework_control_id' => $control->id,
            'user_id' => $user->id,
            'file_path' => $filePath,
            'original_filename' => 'test_file.txt',
            'mime_type' => 'text/plain',
            'hitl_status' => 'accepted',
        ]);

        $response = $this->actingAs($user)->get("/evidence/{$project->id}/export-zip");

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/zip');
    }
}
