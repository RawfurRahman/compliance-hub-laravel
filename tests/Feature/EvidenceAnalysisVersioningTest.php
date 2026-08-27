<?php

namespace Tests\Feature;

use App\Models\Framework;
use App\Models\FrameworkControl;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EvidenceAnalysisVersioningTest extends TestCase
{
    use RefreshDatabase;

    private function makeAuditor(): User
    {
        $role = Role::firstOrCreate(['name' => 'Auditor']);
        $user = User::factory()->create();
        $user->roles()->attach($role->id);

        return $user;
    }

    private function makeEvidence(User $user): array
    {
        $project = Project::create([
            'name' => 'Versioning Project',
            'module_type' => 'iso_27001',
            'user_id' => $user->id,
        ]);

        $framework = Framework::create([
            'name' => 'ISO 27001',
            'slug' => 'iso_27001',
            'is_active' => true,
        ]);

        $control = FrameworkControl::create([
            'framework_id' => $framework->id,
            'control_id' => 'A.5.1',
            'domain' => 'Policies',
            'requirement_description' => 'Information security policies description.',
        ]);

        $evidence = $project->evidenceFiles()->create([
            'framework_control_id' => $control->id,
            'user_id' => $user->id,
            'file_path' => 'evidence/test.txt',
            'original_filename' => 'test.txt',
            'mime_type' => 'text/plain',
            'ai_analysis_status' => 'awaiting_review',
            'ai_observations' => 'Original AI observation.',
            'ai_recommendations' => 'Original AI recommendation.',
            'ai_gaps' => [['gap' => 'Missing access review', 'severity' => 'medium']],
        ]);

        return [$project, $evidence];
    }

    public function test_ai_analysis_run_records_a_version()
    {
        $user = $this->makeAuditor();
        [, $evidence] = $this->makeEvidence($user);

        $evidence->recordAnalysisVersion('ai_analysis');

        $this->assertDatabaseCount('evidence_analysis_versions', 1);
        $version = $evidence->analysisVersions()->first();
        $this->assertEquals(1, $version->version_number);
        $this->assertEquals('ai_analysis', $version->trigger_type);
        $this->assertEquals('Original AI observation.', $version->ai_observations);
        $this->assertNull($version->triggered_by);
    }

    public function test_rejecting_analysis_preserves_previous_result_as_a_version_and_does_not_overwrite_history()
    {
        $auditor = $this->makeAuditor();
        [, $evidence] = $this->makeEvidence($auditor);

        // Simulate the version created by the original AI run.
        $evidence->recordAnalysisVersion('ai_analysis');

        $response = $this->actingAs($auditor)->post("/evidence/{$evidence->id}/ai/reject", [
            'note' => 'Please re-check access review evidence.',
        ]);

        $response->assertStatus(200);

        // Three versions: the original AI run, the snapshot taken before re-analysis reset the fields,
        // and the result of the re-triggered AnalyzeEvidenceJob (QUEUE_CONNECTION=sync in tests).
        $this->assertDatabaseCount('evidence_analysis_versions', 3);

        $versions = $evidence->analysisVersions()->get();
        $this->assertEquals(['ai_analysis', 'reanalysis_requested', 'ai_analysis'], $versions->pluck('trigger_type')->all());

        $rejectionVersion = $versions[1];
        $this->assertEquals($auditor->id, $rejectionVersion->triggered_by);
        $this->assertEquals('Please re-check access review evidence.', $rejectionVersion->reason);
        // The version must retain the ORIGINAL observation, not the "Re-analysis in progress..." placeholder
        // that overwrote the live record — this is the "do not overwrite analysis history" guarantee.
        $this->assertEquals('Original AI observation.', $rejectionVersion->ai_observations);

        // The live record has moved on (re-analysis ran to completion), but history above still holds
        // the original result verbatim — nothing was overwritten in place.
        $evidence->refresh();
        $this->assertNotEquals('Original AI observation.', $evidence->ai_observations);
    }

    public function test_analysis_versions_endpoint_returns_ordered_history()
    {
        $auditor = $this->makeAuditor();
        [, $evidence] = $this->makeEvidence($auditor);

        $evidence->recordAnalysisVersion('ai_analysis');
        $evidence->recordAnalysisVersion('reanalysis_requested', $auditor->id, 'Need more evidence');
        $evidence->update(['ai_observations' => 'Second AI observation.']);
        $evidence->recordAnalysisVersion('ai_analysis');

        $response = $this->actingAs($auditor)->get("/evidence/{$evidence->id}/analysis-versions");

        $response->assertStatus(200);
        $response->assertJsonCount(3);
        $response->assertJsonPath('0.version_number', 1);
        $response->assertJsonPath('1.version_number', 2);
        $response->assertJsonPath('2.version_number', 3);
        $response->assertJsonPath('2.ai_observations', 'Second AI observation.');
    }
}
