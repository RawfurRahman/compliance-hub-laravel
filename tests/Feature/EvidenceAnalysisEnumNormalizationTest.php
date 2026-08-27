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

class EvidenceAnalysisEnumNormalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_normalize_enum_matches_case_insensitively()
    {
        $this->assertEquals('Process', DirectEvidenceAnalysisService::normalizeEnum('process', ['Policy', 'Technical', 'Process']));
        $this->assertEquals('Process', DirectEvidenceAnalysisService::normalizeEnum('PROCESS', ['Policy', 'Technical', 'Process']));
        $this->assertEquals('Medium', DirectEvidenceAnalysisService::normalizeEnum('medium', ['None', 'Low', 'Medium', 'High']));
        $this->assertNull(DirectEvidenceAnalysisService::normalizeEnum('not a real category', ['Policy', 'Technical']));
        $this->assertNull(DirectEvidenceAnalysisService::normalizeEnum('', ['Policy']));
        $this->assertNull(DirectEvidenceAnalysisService::normalizeEnum(null, ['Policy']));
    }

    public function test_lowercase_ai_response_still_populates_the_dropdown_value()
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $project = Project::create([
            'name' => 'Enum Normalization Project',
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
        Storage::disk('public')->put('evidence/test.txt', 'content');
        $evidence = $project->evidenceFiles()->create([
            'framework_control_id' => $control->id,
            'user_id' => $user->id,
            'file_path' => 'evidence/test.txt',
            'original_filename' => 'test.txt',
            'mime_type' => 'text/plain',
        ]);

        // Model returns lowercase/mismatched casing, as vision models sometimes do.
        Http::fake([
            '*/api/generate' => Http::response(['response' => json_encode([
                'observations' => 'Observed X.',
                'recommendations' => 'Do Y.',
                'gaps' => [],
                'is_compliant' => false,
                'risk_rating' => 'medium',
                'gap_category' => 'process',
            ])]),
        ]);

        app(DirectEvidenceAnalysisService::class)->process($evidence);

        $evidence->refresh();
        $this->assertEquals('Medium', $evidence->analysis_report_data['risk_rating']);
        $this->assertEquals('Process', $evidence->analysis_report_data['gap_category']);
    }
}
