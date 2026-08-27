<?php

namespace Tests\Feature;

use App\Models\Framework;
use App\Models\FrameworkControl;
use App\Models\Project;
use App\Models\User;
use App\Services\DirectEvidenceAnalysisService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EvidenceAnalysisReportFieldsTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_full_ai_json_response_populates_every_gap_assessment_field()
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $project = Project::create([
            'name' => 'Report Fields Project',
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
            'control_id' => 'A.9.2',
            'domain' => 'Access Control',
            'requirement_description' => 'Periodic access review must be performed.',
        ]);

        Storage::disk('public')->put('evidence/screenshot.png', 'fake-image-bytes');

        $evidence = $project->evidenceFiles()->create([
            'framework_control_id' => $control->id,
            'user_id' => $user->id,
            'file_path' => 'evidence/screenshot.png',
            'original_filename' => 'Screenshot 2026-06-13 at 2.25.41 PM (1).png',
            'mime_type' => 'image/png',
        ]);

        // A realistic full response, as the model should now produce with the fixed prompt.
        $aiJson = json_encode([
            'observations' => '1. Screenshot shows a single user session with no review log visible.',
            'recommendations' => '1. Provide the quarterly access review report.',
            'gaps' => [['gap' => 'No periodic access review evidence visible in the screenshot.', 'severity' => 'medium']],
            'is_compliant' => false,
            'risk_rating' => 'Medium',
            'gap_description' => 'No periodic access review evidence visible in the screenshot.',
            'impact_assessment' => 'Stale access rights may go undetected, increasing unauthorized access risk.',
            'gap_category' => 'Process',
            'non_compliant_details' => 'The screenshot does not show any review log, sign-off, or date of last review.',
            'compliant_description' => '',
            'remediation_plan' => 'Implement and document a quarterly access review process.',
            'test_results' => 'Screenshot shows a user list with no review metadata.',
            'meets_standard' => false,
        ]);

        Http::fake([
            '*/api/generate' => Http::response(['response' => $aiJson]),
        ]);

        app(DirectEvidenceAnalysisService::class)->process($evidence);

        $evidence->refresh();
        $data = $evidence->analysis_report_data;

        $this->assertNotNull($data, 'analysis_report_data must not be null after a successful full AI response.');

        $expected = [
            'observation' => '1. Screenshot shows a single user session with no review log visible.',
            'recommended_action' => '1. Provide the quarterly access review report.',
            'evidence_provided' => 'Screenshot 2026-06-13 at 2.25.41 PM (1).png',
            'is_compliant' => false,
            'risk_rating' => 'Medium',
            'gap_description' => 'No periodic access review evidence visible in the screenshot.',
            'impact_assessment' => 'Stale access rights may go undetected, increasing unauthorized access risk.',
            'gap_category' => 'Process',
            'non_compliant_details' => 'The screenshot does not show any review log, sign-off, or date of last review.',
            'remediation_plan' => 'Implement and document a quarterly access review process.',
            'test_results' => 'Screenshot shows a user list with no review metadata.',
            'meets_standard' => false,
        ];

        foreach ($expected as $key => $value) {
            $this->assertArrayHasKey($key, $data, "analysis_report_data is missing key '{$key}'");
            $this->assertEquals($value, $data[$key], "analysis_report_data['{$key}'] did not match the AI response");
        }
    }

    public function test_n8n_callback_populates_every_gap_assessment_field()
    {
        $user = User::factory()->create();
        $project = Project::create([
            'name' => 'N8n Callback Project',
            'module_type' => 'iso_27001',
            'user_id' => $user->id,
        ]);
        $framework = Framework::create(['name' => 'ISO 27001', 'slug' => 'iso_27001', 'is_active' => true]);
        $control = FrameworkControl::create([
            'framework_id' => $framework->id,
            'control_id' => 'A.9.2',
            'domain' => 'Access Control',
            'requirement_description' => 'Periodic access review must be performed.',
        ]);
        $evidence = $project->evidenceFiles()->create([
            'framework_control_id' => $control->id,
            'user_id' => $user->id,
            'file_path' => 'evidence/screenshot.png',
            'original_filename' => 'screenshot.png',
            'mime_type' => 'image/png',
        ]);

        $payload = [
            'evidence_file_id' => $evidence->id,
            'status' => 'completed',
            'observations' => '1. No review log visible.',
            'recommendations' => '1. Provide review evidence.',
            'gaps' => [['gap' => 'Missing review log', 'severity' => 'medium']],
            'report_fields' => [
                'is_compliant' => false,
                'risk_rating' => 'Medium',
                'gap_description' => 'Missing review log',
                'impact_assessment' => 'Access risk undetected.',
                'gap_category' => 'Process',
                'non_compliant_details' => 'No log present.',
                'compliant_description' => '',
                'remediation_plan' => 'Add quarterly review process.',
                'test_results' => 'Screenshot has no log.',
                'meets_standard' => false,
            ],
        ];

        $timestamp = time();
        $signature = hash_hmac('sha256', $timestamp.'.'.$evidence->id, env('N8N_WEBHOOK_SECRET', ''));

        $response = $this->withHeaders([
            'X-N8n-Api-Key' => env('N8N_API_KEY', 'n8n_api_local_dev_key_12345'),
        ])->postJson('/api/n8n/ai-callback', $payload);

        $response->assertStatus(200);

        $evidence->refresh();
        $data = $evidence->analysis_report_data;

        $this->assertNotNull($data);
        $this->assertEquals('Process', $data['gap_category']);
        $this->assertEquals('Missing review log', $data['gap_description']);
        $this->assertEquals('Access risk undetected.', $data['impact_assessment']);
        $this->assertEquals('No log present.', $data['non_compliant_details']);
        $this->assertEquals('Add quarterly review process.', $data['remediation_plan']);
        $this->assertEquals('Screenshot has no log.', $data['test_results']);
        $this->assertFalse($data['meets_standard']);
        $this->assertEquals('screenshot.png', $data['evidence_provided']);
    }
}
