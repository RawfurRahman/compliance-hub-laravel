<?php

namespace Tests\Feature;

use App\Models\Framework;
use App\Models\FrameworkControl;
use App\Models\Project;
use App\Models\User;
use App\Services\DirectEvidenceAnalysisService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EvidenceAnalysisPromptTest extends TestCase
{
    use RefreshDatabase;

    private function makeEvidence(?string $requirementDescription): array
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $project = Project::create([
            'name' => 'Prompt Test Project',
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
            'requirement_description' => $requirementDescription,
        ]);

        Storage::disk('public')->put('evidence/test.txt', 'evidence content');

        $evidence = $project->evidenceFiles()->create([
            'framework_control_id' => $control->id,
            'user_id' => $user->id,
            'file_path' => 'evidence/test.txt',
            'original_filename' => 'test.txt',
            'mime_type' => 'text/plain',
        ]);

        return [$project, $evidence, $control];
    }

    public function test_empty_requirement_description_does_not_produce_a_blank_prompt()
    {
        [, $evidence, $control] = $this->makeEvidence('');

        Http::fake([
            '*/api/generate' => Http::response(['response' => '{"observations":"Real analysis.","recommendations":"None","gaps":[]}']),
        ]);

        app(DirectEvidenceAnalysisService::class)->process($evidence);

        Http::assertSent(function (Request $request) use ($control) {
            if (! str_contains($request->url(), '/api/generate')) {
                return true;
            }
            $prompt = $request->data()['prompt'] ?? '';

            // The requirement section must not be left blank -- it must explicitly
            // say the description is missing, referencing the real control id.
            $this->assertStringNotContainsString("Requirement:\n\n", $prompt);
            $this->assertStringContainsString('No requirement description is on file', $prompt);
            $this->assertStringContainsString($control->control_id, $prompt);

            return true;
        });
    }

    public function test_populated_requirement_description_is_sent_verbatim()
    {
        [, $evidence] = $this->makeEvidence('Access control procedures must be documented and reviewed periodically.');

        Http::fake([
            '*/api/generate' => Http::response(['response' => '{"observations":"Real analysis.","recommendations":"None","gaps":[]}']),
        ]);

        app(DirectEvidenceAnalysisService::class)->process($evidence);

        Http::assertSent(function (Request $request) {
            if (! str_contains($request->url(), '/api/generate')) {
                return true;
            }
            $prompt = $request->data()['prompt'] ?? '';
            $this->assertStringContainsString('Access control procedures must be documented and reviewed periodically.', $prompt);
            $this->assertStringNotContainsString('No requirement description is on file', $prompt);

            return true;
        });
    }

    public function test_prompt_example_uses_placeholder_syntax_not_literal_sample_answers()
    {
        [, $evidence] = $this->makeEvidence('Some real requirement text.');

        Http::fake([
            '*/api/generate' => Http::response(['response' => '{"observations":"Real analysis.","recommendations":"None","gaps":[]}']),
        ]);

        app(DirectEvidenceAnalysisService::class)->process($evidence);

        Http::assertSent(function (Request $request) {
            if (! str_contains($request->url(), '/api/generate')) {
                return true;
            }
            $prompt = $request->data()['prompt'] ?? '';

            // The old prompt's literal example text ("1. First specific observation.")
            // was concrete enough that a small vision model would sometimes copy it
            // verbatim instead of analyzing the evidence. The template must now use
            // obviously-non-literal bracketed placeholders instead.
            $this->assertStringNotContainsString('First specific observation', $prompt);
            $this->assertStringNotContainsString('Specific gap description', $prompt);
            $this->assertStringContainsString('FORMAT TEMPLATE ONLY', $prompt);

            return true;
        });
    }
}
