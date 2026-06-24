<?php

namespace App\Jobs;

use App\Models\EvidenceFile;
use App\Services\DirectEvidenceAnalysisService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class AnalyzeEvidenceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 10;

    public array $backoff = [30, 60, 120, 300, 600, 900, 1800, 3600, 7200];

    public function __construct(
        public int $evidenceFileId
    ) {}

    public function handle(DirectEvidenceAnalysisService $service): void
    {
        $evidence = EvidenceFile::find($this->evidenceFileId);

        if (!$evidence) {
            Log::warning("AnalyzeEvidenceJob: EvidenceFile {$this->evidenceFileId} not found, skipping.");
            return;
        }

        $service->process($evidence);
    }

    public function failed(\Throwable $e): void
    {
        Log::error("AnalyzeEvidenceJob permanently failed for evidence_file_id {$this->evidenceFileId}: " . $e->getMessage());

        $evidence = EvidenceFile::find($this->evidenceFileId);
        if ($evidence) {
            $evidence->update([
                'ai_analysis_status' => 'failed',
                'scan_status' => 'failed',
                'ai_observations' => 'Analysis failed after multiple retries: ' . $e->getMessage(),
                'ai_recommendations' => 'Please try uploading the evidence again or contact support.',
            ]);
        }
    }
}
