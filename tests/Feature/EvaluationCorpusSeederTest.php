<?php

namespace Tests\Feature;

use App\Models\EvaluationCorpusItem;
use App\Models\Framework;
use App\Models\FrameworkControl;
use App\Services\EvaluationRunService;
use Database\Seeders\EvaluationCorpusSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EvaluationCorpusSeederTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Create the four frameworks the corpus depends on, mirroring the slugs
     * already present in the application's frameworks table.
     */
    protected function seedFrameworks(): void
    {
        $frameworks = [
            'pci_dss' => 'PCI DSS',
            'iso_27001' => 'ISO 27001',
            'bb_ict' => 'BB ICT Guidelines',
            'hitrust' => 'HITRUST CSF',
        ];

        foreach ($frameworks as $slug => $name) {
            Framework::create([
                'slug' => $slug,
                'name' => $name,
                'is_active' => true,
            ]);
        }
    }

    /** @test */
    public function it_seeds_sixty_items_with_the_required_framework_split()
    {
        $this->seedFrameworks();

        $this->seed(EvaluationCorpusSeeder::class);

        $this->assertDatabaseCount('evaluation_corpus_items', 60);

        $counts = EvaluationCorpusItem::query()
            ->join('framework_controls', 'framework_controls.id', '=', 'evaluation_corpus_items.framework_control_id')
            ->join('frameworks', 'frameworks.id', '=', 'framework_controls.framework_id')
            ->selectRaw('frameworks.slug, COUNT(*) as total')
            ->groupBy('frameworks.slug')
            ->pluck('total', 'slug');

        $this->assertEquals(24, $counts->get('pci_dss'));
        $this->assertEquals(14, $counts->get('iso_27001'));
        $this->assertEquals(12, $counts->get('bb_ict'));
        $this->assertEquals(10, $counts->get('hitrust'));
    }

    /** @test */
    public function it_assigns_the_balanced_ground_truth_verdict_split()
    {
        $this->seedFrameworks();
        $this->seed(EvaluationCorpusSeeder::class);

        $counts = EvaluationCorpusItem::query()
            ->selectRaw('ground_truth, COUNT(*) as total')
            ->groupBy('ground_truth')
            ->pluck('total', 'ground_truth');

        $this->assertEquals(24, $counts->get('compliant'));
        $this->assertEquals(18, $counts->get('partial'));
        $this->assertEquals(18, $counts->get('non_compliant'));
    }

    /** @test */
    public function it_spreads_evidence_types_evenly_across_the_corpus()
    {
        $this->seedFrameworks();
        $this->seed(EvaluationCorpusSeeder::class);

        $counts = EvaluationCorpusItem::query()
            ->selectRaw('evidence_type, COUNT(*) as total')
            ->groupBy('evidence_type')
            ->pluck('total', 'evidence_type');

        foreach (EvaluationCorpusItem::EVIDENCE_TYPES as $type) {
            $this->assertEquals(12, $counts->get($type), "Evidence type {$type} should appear 12 times");
        }
    }

    /** @test */
    public function it_splits_the_corpus_evenly_between_the_two_chapters()
    {
        $this->seedFrameworks();
        $this->seed(EvaluationCorpusSeeder::class);

        $this->assertEquals(30, EvaluationCorpusItem::where('chapter', 'chapter_6')->count());
        $this->assertEquals(30, EvaluationCorpusItem::where('chapter', 'chapter_7')->count());
    }

    /** @test */
    public function every_item_references_a_valid_control_in_the_matching_framework()
    {
        $this->seedFrameworks();
        $this->seed(EvaluationCorpusSeeder::class);

        foreach (EvaluationCorpusItem::with('frameworkControl.framework')->get() as $item) {
            $this->assertNotNull($item->frameworkControl, "Missing control for corpus item {$item->id}");
            $this->assertContains(
                $item->frameworkControl->framework->slug,
                ['pci_dss', 'iso_27001', 'bb_ict', 'hitrust'],
                "Corpus item {$item->id} must belong to one of the four evaluation frameworks"
            );
        }
    }

    /** @test */
    public function every_item_has_a_rationale_and_verdict_consistent_gaps()
    {
        $this->seedFrameworks();
        $this->seed(EvaluationCorpusSeeder::class);

        foreach (EvaluationCorpusItem::get() as $item) {
            $this->assertNotEmpty($item->truth_rationale, "Item {$item->id} is missing a truth rationale");
            $this->assertIsArray($item->expected_gaps);

            $gapCount = count($item->expected_gaps);

            if ($item->ground_truth === 'compliant') {
                $this->assertEquals(0, $gapCount, "Compliant item {$item->id} should declare zero gaps");
            } else {
                $this->assertGreaterThan(0, $gapCount, "Non-compliant item {$item->id} should declare at least one gap");

                foreach ($item->expected_gaps as $gap) {
                    $this->assertArrayHasKey('gap', $gap);
                    $this->assertArrayHasKey('severity', $gap);
                    $this->assertArrayHasKey('remediation', $gap);
                }
            }
        }
    }

    /** @test */
    public function it_creates_missing_pci_controls_and_is_idempotent()
    {
        $this->seedFrameworks();
        $this->seed(EvaluationCorpusSeeder::class);

        $this->assertEquals(24, FrameworkControl::whereHas('framework', fn ($q) => $q->where('slug', 'pci_dss'))->count());

        $this->seed(EvaluationCorpusSeeder::class);

        $this->assertDatabaseCount('evaluation_corpus_items', 60);
        $this->assertEquals(24, FrameworkControl::whereHas('framework', fn ($q) => $q->where('slug', 'pci_dss'))->count());
    }

    /** @test */
    public function mock_data_is_sanitized()
    {
        $this->seedFrameworks();
        $this->seed(EvaluationCorpusSeeder::class);

        foreach (EvaluationCorpusItem::get() as $item) {
            $this->assertStringContainsString('XYZ_Bank', $item->evidence_name);
            $this->assertMatchesRegularExpression('/^[A-Za-z0-9_.]+$/', $item->evidence_name);
            $this->assertStringNotContainsString('@', $item->evidence_name);
        }
    }

    /** @test */
    public function model_visible_evidence_never_contains_ground_truth_material()
    {
        $this->seedFrameworks();
        $this->seed(EvaluationCorpusSeeder::class);

        $runService = app(EvaluationRunService::class);
        $labels = ['compliant', 'partial', 'non_compliant'];

        foreach (EvaluationCorpusItem::with('frameworkControl')->get() as $item) {
            $body = $runService->evidenceBody($item);

            $this->assertNotSame('', $body, "Item {$item->id} must have model-visible evidence content");
            $this->assertSame(
                trim((string) $item->evidence_summary),
                $body,
                "Item {$item->id} model-visible body must be evidence_summary only"
            );

            foreach ($labels as $label) {
                $this->assertStringNotContainsStringIgnoringCase(
                    $label,
                    $body,
                    "Item {$item->id} evidence must not contain the verdict label '{$label}'"
                );
            }

            $this->assertStringNotContainsStringIgnoringCase(
                (string) $item->truth_rationale,
                $body,
                "Item {$item->id} evidence must not embed truth_rationale"
            );

            foreach ((array) $item->expected_gaps as $expectedGap) {
                foreach (['gap', 'remediation'] as $key) {
                    if (! empty($expectedGap[$key])) {
                        $this->assertStringNotContainsStringIgnoringCase(
                            (string) $expectedGap[$key],
                            $body,
                            "Item {$item->id} evidence must not embed expected_gaps {$key} text"
                        );
                    }
                }
            }
        }
    }
}
