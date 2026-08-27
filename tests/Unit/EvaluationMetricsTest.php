<?php

namespace Tests\Unit;

use App\Models\EvaluationCorpusItem;
use App\Models\EvaluationRunItem;
use App\Models\EvidenceFile;
use App\Models\Framework;
use App\Models\FrameworkControl;
use App\Models\Project;
use App\Services\DirectEvidenceAnalysisService;
use App\Services\EvaluationCsvExporter;
use App\Services\EvaluationRunService;
use App\Services\EvaluationSyntheticFileFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EvaluationMetricsTest extends TestCase
{
    use RefreshDatabase;

    private function makeEvidence(Project $project, string $filename, bool $fallback): EvidenceFile
    {
        return EvidenceFile::create([
            'project_id' => $project->id,
            'file_path' => 'mock/evaluation/'.$filename,
            'original_filename' => $filename,
            'mime_type' => 'image/png',
            'ai_parse_fallback' => $fallback,
        ]);
    }

    public function test_metrics_returns_correct_values(): void
    {
        $service = new EvaluationRunService(
            $this->app->make(EvaluationSyntheticFileFactory::class),
            $this->app->make(EvaluationCsvExporter::class),
            $this->app->make(DirectEvidenceAnalysisService::class)
        );

        $runKey = 'test_run_'.now()->format('Ymd_His');

        $project = Project::create([
            'name' => 'Evaluation Metrics Test',
            'module_type' => 'evaluation',
        ]);

        // Create framework and distinct framework controls for corpus items
        // (framework_control_id is unique on evaluation_corpus_items)
        $framework = Framework::create(['name' => 'Test Framework', 'slug' => 'test_framework']);
        $controls = [];
        foreach (['CTRL-001', 'CTRL-002', 'CTRL-003'] as $controlId) {
            $controls[$controlId] = FrameworkControl::create([
                'framework_id' => $framework->id,
                'control_id' => $controlId,
                'requirement_description' => 'Test requirement '.$controlId,
            ]);
        }

        // Create corpus items with different ground truth values
        $compliantItem = EvaluationCorpusItem::create([
            'framework_control_id' => $controls['CTRL-001']->id,
            'chapter' => 'chapter_6',
            'evidence_type' => 'screenshot',
            'evidence_name' => 'evidence_compliant.png',
            'evidence_summary' => 'Mock compliant evidence.',
            'ground_truth' => 'compliant',
            'truth_rationale' => 'Rationale for compliant.',
            'expected_gaps' => [],
        ]);
        $partialItem = EvaluationCorpusItem::create([
            'framework_control_id' => $controls['CTRL-002']->id,
            'chapter' => 'chapter_6',
            'evidence_type' => 'screenshot',
            'evidence_name' => 'evidence_partial.png',
            'evidence_summary' => 'Mock partial evidence.',
            'ground_truth' => 'partial',
            'truth_rationale' => 'Rationale for partial.',
            'expected_gaps' => [],
        ]);
        $nonCompliantItem = EvaluationCorpusItem::create([
            'framework_control_id' => $controls['CTRL-003']->id,
            'chapter' => 'chapter_6',
            'evidence_type' => 'screenshot',
            'evidence_name' => 'evidence_non_compliant.png',
            'evidence_summary' => 'Mock non-compliant evidence.',
            'ground_truth' => 'non_compliant',
            'truth_rationale' => 'Rationale for non_compliant.',
            'expected_gaps' => [],
        ]);

        // Create evaluation run items with known ground truth and predicted verdicts
        // to produce specific confusion matrix and per-class metrics
        $ef1 = $this->makeEvidence($project, 'e1.png', false);
        $ef2 = $this->makeEvidence($project, 'e2.png', true);
        $ef3 = $this->makeEvidence($project, 'e3.png', false);
        $ef4 = $this->makeEvidence($project, 'e4.png', false);
        $ef5 = $this->makeEvidence($project, 'e5.png', false);

        EvaluationRunItem::create([
            'run_key' => $runKey,
            'evaluation_corpus_item_id' => $compliantItem->id,
            'ground_truth' => 'compliant',
            'predicted_verdict' => 'compliant',
            'verdict_match' => true,
            'evidence_file_id' => $ef1->id,
        ]);

        EvaluationRunItem::create([
            'run_key' => $runKey,
            'evaluation_corpus_item_id' => $compliantItem->id,
            'ground_truth' => 'compliant',
            'predicted_verdict' => 'compliant',
            'verdict_match' => true,
            'evidence_file_id' => $ef2->id,
        ]);

        EvaluationRunItem::create([
            'run_key' => $runKey,
            'evaluation_corpus_item_id' => $partialItem->id,
            'ground_truth' => 'partial',
            'predicted_verdict' => 'partial',
            'verdict_match' => true,
            'evidence_file_id' => $ef3->id,
        ]);

        EvaluationRunItem::create([
            'run_key' => $runKey,
            'evaluation_corpus_item_id' => $nonCompliantItem->id,
            'ground_truth' => 'non_compliant',
            'predicted_verdict' => 'non_compliant',
            'verdict_match' => true,
            'evidence_file_id' => $ef4->id,
        ]);

        EvaluationRunItem::create([
            'run_key' => $runKey,
            'evaluation_corpus_item_id' => $nonCompliantItem->id,
            'ground_truth' => 'non_compliant',
            'predicted_verdict' => 'compliant',
            'verdict_match' => false,
            'evidence_file_id' => $ef5->id,
        ]);

        $metrics = $service->metrics($runKey);

        // Confusion matrix: [actual][predicted]
        $this->assertEquals(2, $metrics['confusion']['compliant']['compliant']);
        $this->assertEquals(0, $metrics['confusion']['compliant']['partial']);
        $this->assertEquals(0, $metrics['confusion']['compliant']['non_compliant']);
        $this->assertEquals(0, $metrics['confusion']['partial']['compliant']);
        $this->assertEquals(1, $metrics['confusion']['partial']['partial']);
        $this->assertEquals(0, $metrics['confusion']['partial']['non_compliant']);
        $this->assertEquals(1, $metrics['confusion']['non_compliant']['compliant']); // critical error
        $this->assertEquals(0, $metrics['confusion']['non_compliant']['partial']);
        $this->assertEquals(1, $metrics['confusion']['non_compliant']['non_compliant']);

        // Per-class metrics (one-vs-rest)
        // compliant: TP=2, FP=1 (non_compliant predicted compliant), FN=0
        $this->assertEquals(0.67, $metrics['per_class']['compliant']['precision']);
        $this->assertEquals(1.0, $metrics['per_class']['compliant']['recall']);
        $this->assertEquals(0.8, $metrics['per_class']['compliant']['f1']);

        // partial: TP=1, FP=0, FN=0
        $this->assertEquals(1.0, $metrics['per_class']['partial']['precision']);
        $this->assertEquals(1.0, $metrics['per_class']['partial']['recall']);
        $this->assertEquals(1.0, $metrics['per_class']['partial']['f1']);

        // non_compliant: TP=1, FP=0, FN=1 (non_compliant predicted compliant)
        $this->assertEquals(1.0, $metrics['per_class']['non_compliant']['precision']);
        $this->assertEquals(0.5, $metrics['per_class']['non_compliant']['recall']);
        $this->assertEquals(0.67, $metrics['per_class']['non_compliant']['f1']);

        // macro_f1 = mean of the three f1 values = (0.8 + 1.0 + 0.67) / 3 = 0.82
        $this->assertEquals(0.82, $metrics['macro_f1']);

        // accuracy: 4/5 matches (items 1,2,3,4 have verdict_match=true, item 5 has false)
        $this->assertEquals(80.0, $metrics['accuracy']);

        // parse_failure_rate: 1 out of 5 items has ai_parse_fallback true => 20%
        $this->assertEquals(20.0, $metrics['parse_failure_rate']);

        // critical_error_count: 1 (non_compliant predicted compliant)
        $this->assertEquals(1, $metrics['critical_error_count']);

        // latency: mean and median of scan_ms, analysis_ms and total_ms
        $this->assertArrayHasKey('latency', $metrics);
        $this->assertArrayHasKey('scan_ms', $metrics['latency']);
        $this->assertArrayHasKey('analysis_ms', $metrics['latency']);
        $this->assertArrayHasKey('total_ms', $metrics['latency']);
        $this->assertArrayHasKey('mean', $metrics['latency']['scan_ms']);
        $this->assertArrayHasKey('median', $metrics['latency']['scan_ms']);
    }

    public function test_metrics_zero_predictions_guard(): void
    {
        $service = new EvaluationRunService(
            $this->app->make(EvaluationSyntheticFileFactory::class),
            $this->app->make(EvaluationCsvExporter::class),
            $this->app->make(DirectEvidenceAnalysisService::class)
        );

        $runKey = 'test_run_zero_'.now()->format('Ymd_His');

        $project = Project::create([
            'name' => 'Evaluation Zero-Prediction Test',
            'module_type' => 'evaluation',
        ]);

        // Create framework and distinct framework controls
        $framework = Framework::create(['name' => 'Test Framework', 'slug' => 'test_framework2']);
        $controls = [];
        foreach (['CTRL-100', 'CTRL-200'] as $controlId) {
            $controls[$controlId] = FrameworkControl::create([
                'framework_id' => $framework->id,
                'control_id' => $controlId,
                'requirement_description' => 'Test requirement '.$controlId,
            ]);
        }

        // Create corpus items
        $compliantItem = EvaluationCorpusItem::create([
            'framework_control_id' => $controls['CTRL-100']->id,
            'chapter' => 'chapter_6',
            'evidence_type' => 'screenshot',
            'evidence_name' => 'evidence_zero_compliant.png',
            'evidence_summary' => 'Mock compliant evidence.',
            'ground_truth' => 'compliant',
            'truth_rationale' => 'Rationale for compliant.',
            'expected_gaps' => [],
        ]);
        $partialItem = EvaluationCorpusItem::create([
            'framework_control_id' => $controls['CTRL-200']->id,
            'chapter' => 'chapter_6',
            'evidence_type' => 'screenshot',
            'evidence_name' => 'evidence_zero_partial.png',
            'evidence_summary' => 'Mock partial evidence.',
            'ground_truth' => 'partial',
            'truth_rationale' => 'Rationale for partial.',
            'expected_gaps' => [],
        ]);

        // Create run items where one class (partial) has zero predictions
        EvaluationRunItem::create([
            'run_key' => $runKey,
            'evaluation_corpus_item_id' => $compliantItem->id,
            'ground_truth' => 'compliant',
            'predicted_verdict' => 'compliant',
            'evidence_file_id' => $this->makeEvidence($project, 'zero_e1.png', false)->id,
        ]);

        // Only one item with ground_truth=partial but NO item with predicted_verdict=partial
        EvaluationRunItem::create([
            'run_key' => $runKey,
            'evaluation_corpus_item_id' => $partialItem->id,
            'ground_truth' => 'partial',
            'predicted_verdict' => 'compliant',
            'evidence_file_id' => $this->makeEvidence($project, 'zero_e2.png', false)->id,
        ]);

        $metrics = $service->metrics($runKey);

        // partial class has zero predictions: precision, recall, f1 should all be 0.0
        $this->assertEquals(0.0, $metrics['per_class']['partial']['precision']);
        $this->assertEquals(0.0, $metrics['per_class']['partial']['recall']);
        $this->assertEquals(0.0, $metrics['per_class']['partial']['f1']);

        // macro_f1 should account for the 0 f1 (mean of 0, 1.0, 1.0 = 0.67)
        $this->assertGreaterThan(0, $metrics['macro_f1']);

        // critical_error_count should be 0 (no non_compliant items with predicted compliant)
        $this->assertEquals(0, $metrics['critical_error_count']);
    }
}
