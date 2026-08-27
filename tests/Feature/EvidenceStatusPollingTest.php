<?php

namespace Tests\Feature;

use App\Models\Framework;
use App\Models\FrameworkControl;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EvidenceStatusPollingTest extends TestCase
{
    use RefreshDatabase;

    public function test_status_poll_endpoint_returns_analysis_report_data()
    {
        $user = User::factory()->create();
        $project = Project::create([
            'name' => 'Polling Project',
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
            'file_path' => 'evidence/test.txt',
            'original_filename' => 'test.txt',
            'mime_type' => 'text/plain',
            'ai_analysis_status' => 'awaiting_review',
            'analysis_report_data' => [
                'gap_category' => 'Process',
                'risk_rating' => 'Medium',
                'is_compliant' => false,
                'impact_assessment' => 'Access risk undetected.',
            ],
        ]);

        // Simulates the poll that runs every 5s on the Evidence Hub page after a
        // re-analysis: the client must be able to pick up the fresh structured
        // fields without a full page reload.
        $response = $this->actingAs($user)->getJson("/evidence/{$evidence->id}/status");

        $response->assertStatus(200);
        $response->assertJsonPath('analysis_report_data.gap_category', 'Process');
        $response->assertJsonPath('analysis_report_data.risk_rating', 'Medium');
        $response->assertJsonPath('analysis_report_data.impact_assessment', 'Access risk undetected.');
    }
}
