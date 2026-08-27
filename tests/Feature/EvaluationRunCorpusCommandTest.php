<?php

namespace Tests\Feature;

use App\Models\EvaluationRunItem;
use App\Models\EvidenceFile;
use App\Models\Framework;
use Database\Seeders\EvaluationCorpusSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EvaluationRunCorpusCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake('quarantine');

        Http::fake([
            '*/scan' => Http::response(['infected' => false, 'ok' => true]),
            '*/api/generate' => Http::response(['response' => '{"observations":"1. Verified from evidence.","recommendations":"1. Continue monitoring.","gaps":[]}']),
        ]);
    }

    private function seedCorpus(): void
    {
        $frameworks = [
            'pci_dss' => 'PCI DSS',
            'iso_27001' => 'ISO 27001',
            'bb_ict' => 'BB ICT Guidelines',
            'hitrust' => 'HITRUST CSF',
        ];

        foreach ($frameworks as $slug => $name) {
            Framework::create(['slug' => $slug, 'name' => $name, 'is_active' => true]);
        }

        $this->seed(EvaluationCorpusSeeder::class);
    }

    private function tempCsvPath(): string
    {
        return rtrim(sys_get_temp_dir(), '\\/').'/system-output-'.uniqid().'.csv';
    }

    /** @test */
    public function it_runs_a_sync_subset_and_cleans_up_stuck_items()
    {
        $this->seedCorpus();
        $csv = $this->tempCsvPath();

        $this->artisan('evaluation:run-corpus', [
            '--limit' => 3,
            '--sync' => true,
            '--output' => $csv,
        ])->assertSuccessful();

        $this->assertDatabaseCount('evidence_files', 3);

        $runItems = EvaluationRunItem::query()
            ->whereNull('evidence_file_id')
            ->get();
        $this->assertCount(0, $runItems);

        foreach (EvaluationRunItem::get() as $item) {
            $this->assertNotNull($item->evidence_file_id);
            $this->assertContains($item->ai_analysis_status,
                ['awaiting_review', 'completed', 'approved', 'failed', 'skipped_due_to_scan']);
            $this->assertNotNull($item->scan_ms);
            $this->assertNotNull($item->analysis_ms);
            $this->assertNotNull($item->total_ms);
            $this->assertGreaterThanOrEqual(0, $item->scan_ms);
            $this->assertGreaterThanOrEqual(0, $item->total_ms);
            $this->assertNotNull($item->predicted_verdict);
            $this->assertNotNull($item->verdict_match);
        }

        $this->assertTrue(File::exists($csv));
        $contents = File::get($csv);
        $lines = array_filter(explode("\n", trim($contents)));
        $this->assertEquals(4, count($lines), 'Header plus three data rows');
        $this->assertStringContainsString('compliant', $contents);
        File::delete($csv);
    }

    /** @test */
    public function it_uses_the_real_scan_and_analysis_endpoints()
    {
        $this->seedCorpus();

        $this->artisan('evaluation:run-corpus', [
            '--limit' => 1,
            '--sync' => true,
        ])->assertSuccessful();

        Http::assertSent(fn (Request $request) => str_contains($request->url(), '/scan'));
        Http::assertSent(fn (Request $request) => str_contains($request->url(), '/api/generate'));

        $evidence = EvidenceFile::first();
        $this->assertEquals('clean', $evidence->scan_status);
        $this->assertCount(0, $evidence->ai_gaps ?? []);
    }

    /** @test */
    public function it_supports_framework_subset()
    {
        $this->seedCorpus();

        $this->artisan('evaluation:run-corpus', [
            '--framework' => 'pci_dss',
            '--sync' => true,
        ])->assertSuccessful();

        $this->assertEquals(24, EvaluationRunItem::count());
        $this->assertEquals(24, EvidenceFile::count());
    }

    /** @test */
    public function it_supports_chapter_subset()
    {
        $this->seedCorpus();

        $this->artisan('evaluation:run-corpus', [
            '--chapter' => 'chapter_6',
            '--sync' => true,
        ])->assertSuccessful();

        $this->assertEquals(30, EvaluationRunItem::count());
        $this->assertSame(
            0,
            EvaluationRunItem::where('chapter', 'chapter_7')->count()
        );
    }

    /** @test */
    public function it_supports_the_twenty_item_baseline_arm()
    {
        $this->seedCorpus();

        $this->artisan('evaluation:run-corpus', [
            '--baseline' => true,
            '--sync' => true,
        ])->assertSuccessful();

        $this->assertEquals(20, EvaluationRunItem::count());
        $this->assertEquals(20, EvidenceFile::count());
    }

    /** @test */
    public function it_rejects_unknown_options()
    {
        $this->seedCorpus();

        $this->artisan('evaluation:run-corpus', [
            '--framework' => 'nonsense',
        ])->assertExitCode(1);
    }

    /** @test */
    public function it_runs_queued_default_without_leaving_stuck_items()
    {
        $this->seedCorpus();

        $this->artisan('evaluation:run-corpus', [
            '--limit' => 3,
        ])->assertSuccessful();

        $this->assertEquals(3, EvaluationRunItem::count());

        foreach (EvaluationRunItem::get() as $item) {
            $this->assertContains($item->ai_analysis_status,
                ['awaiting_review', 'completed', 'approved', 'failed', 'skipped_due_to_scan']);
        }
    }
}
