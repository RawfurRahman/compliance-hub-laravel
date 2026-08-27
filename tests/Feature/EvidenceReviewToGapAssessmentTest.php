<?php

namespace Tests\Feature;

use App\Models\AssessmentFinding;
use App\Models\Framework;
use App\Models\FrameworkControl;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EvidenceReviewToGapAssessmentTest extends TestCase
{
    use RefreshDatabase;

    private function makeAuditor(): User
    {
        $role = Role::firstOrCreate(['name' => 'Auditor']);
        $user = User::factory()->create();
        $user->roles()->attach($role->id);

        return $user;
    }

    private function makeEvidence(User $owner): array
    {
        $project = Project::create([
            'name' => 'Review Project '.uniqid(),
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

        $evidence = $project->evidenceFiles()->create([
            'framework_control_id' => $control->id,
            'user_id' => $owner->id,
            'file_path' => 'evidence/test.txt',
            'original_filename' => 'access-review-policy.txt',
            'mime_type' => 'text/plain',
            'ai_analysis_status' => 'awaiting_review',
            'ai_observations' => 'Periodic access review evidence was not provided.',
        ]);

        return [$project, $evidence];
    }

    public function test_auditor_can_review_and_push_full_structured_analysis_to_gap_assessment()
    {
        $owner = $this->makeAuditor();
        [, $evidence] = $this->makeEvidence($owner);

        $payload = [
            // 'workflow_status', not 'status' -- 'status' in analysis_report_data is
            // reserved for the AI's three-class compliance verdict (evaluation harness).
            // Using 'In Progress' here (which differs from is_compliant's own Open/Closed
            // default) proves this field is actually read, not just defaulted.
            'workflow_status' => 'In Progress',
            'risk_rating' => 'Medium',
            'is_compliant' => false,
            'observation' => 'Periodic access review evidence is missing.',
            'gap_description' => 'Periodic access review evidence is missing.',
            'impact_assessment' => 'Unauthorized access may go undetected.',
            'recommended_action' => 'Provide the latest approved access review record.',
            'due_date' => now()->addDays(30)->toDateString(),
            'gap_category' => 'Process',
            'non_compliant_details' => 'No evidence of periodic review was submitted.',
            'compliant_description' => '',
            'remediation_plan' => 'Schedule and document quarterly access reviews.',
            'evidence_provided' => 'access-review-policy.txt',
            'test_results' => 'No review log found for the last quarter.',
            'meets_standard' => false,
            'auditor_notes' => 'Follow up with IT for remediation timeline.',
        ];

        $response = $this->actingAs($owner)->postJson(
            "/evidence/{$evidence->id}/review-and-send-to-gap-assessment",
            $payload
        );

        $response->assertStatus(200);
        $response->assertJsonPath('finding.gap_category', 'Process');

        $finding = AssessmentFinding::findOrFail($response->json('finding_id'));
        $this->assertEquals('In Progress', $finding->status);
        $this->assertEquals('Medium', $finding->risk_rating);
        $this->assertFalse($finding->is_compliant);
        $this->assertEquals('Periodic access review evidence is missing.', $finding->gap_description);
        $this->assertEquals('Unauthorized access may go undetected.', $finding->impact);
        $this->assertEquals('Provide the latest approved access review record.', $finding->recommendation);
        $this->assertEquals(now()->addDays(30)->toDateString(), $finding->due_date->toDateString());
        $this->assertEquals('Process', $finding->gap_category);
        $this->assertEquals('Schedule and document quarterly access reviews.', $finding->remediation_plan);
        $this->assertEquals('source', 'evidence' === $finding->source_type ? 'source' : 'mismatch');
        $this->assertEquals($evidence->id, $finding->source_id);

        $evidence->refresh();
        $this->assertNotNull($evidence->gap_assessment_sent_at);
        $this->assertEquals('Process', $evidence->analysis_report_data['gap_category']);
    }

    public function test_pushing_to_gap_assessment_does_not_clobber_the_ai_compliance_verdict()
    {
        $owner = $this->makeAuditor();
        [, $evidence] = $this->makeEvidence($owner);

        // Simulate a fresh AI analysis having already stored its three-class verdict,
        // as the evaluation harness (EvaluationRunService::resolveVerdict) expects.
        $evidence->update([
            'analysis_report_data' => array_merge($evidence->analysis_report_data ?? [], [
                'status' => 'non_compliant',
            ]),
        ]);

        $response = $this->actingAs($owner)->postJson(
            "/evidence/{$evidence->id}/review-and-send-to-gap-assessment",
            ['workflow_status' => 'Open', 'is_compliant' => false]
        );

        $response->assertStatus(200);

        $evidence->refresh();
        $this->assertEquals('non_compliant', $evidence->analysis_report_data['status'], 'The AI verdict must survive a Gap Assessment submission untouched.');
        $this->assertEquals('Open', $evidence->analysis_report_data['workflow_status']);

        $finding = AssessmentFinding::findOrFail($response->json('finding_id'));
        $this->assertEquals('Open', $finding->status);
    }

    public function test_rejects_review_submission_for_evidence_without_a_linked_control()
    {
        $owner = $this->makeAuditor();
        $project = Project::create([
            'name' => 'No Control Project',
            'module_type' => 'iso_27001',
            'user_id' => $owner->id,
        ]);
        $evidence = $project->evidenceFiles()->create([
            'user_id' => $owner->id,
            'file_path' => 'evidence/test.txt',
            'original_filename' => 'test.txt',
            'mime_type' => 'text/plain',
        ]);

        $response = $this->actingAs($owner)->postJson(
            "/evidence/{$evidence->id}/review-and-send-to-gap-assessment",
            ['risk_rating' => 'Low']
        );

        $response->assertStatus(400);
    }

    public function test_rejects_invalid_gap_category()
    {
        $owner = $this->makeAuditor();
        [, $evidence] = $this->makeEvidence($owner);

        $response = $this->actingAs($owner)->postJson(
            "/evidence/{$evidence->id}/review-and-send-to-gap-assessment",
            ['gap_category' => 'NotARealCategory']
        );

        $response->assertStatus(422);
    }
}
