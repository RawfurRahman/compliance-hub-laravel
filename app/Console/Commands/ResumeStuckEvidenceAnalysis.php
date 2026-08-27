<?php

namespace App\Console\Commands;

use App\Jobs\AnalyzeEvidenceJob;
use App\Models\EvidenceFile;
use App\Services\DirectEvidenceAnalysisService;
use Illuminate\Console\Command;

class ResumeStuckEvidenceAnalysis extends Command
{
    protected $signature = 'evidence:resume-stuck-analysis
        {--minutes=30 : Consider evidence stuck if untouched for this many minutes}
        {--sync : Run analysis synchronously instead of dispatching queued jobs}';

    protected $description = 'Re-dispatch AI analysis for evidence stuck in a pending/processing state';

    public function handle(): int
    {
        $cutoff = now()->subMinutes((int) $this->option('minutes'));

        $files = EvidenceFile::query()
            ->whereIn('ai_analysis_status', ['pending', 'processing'])
            ->where('updated_at', '<', $cutoff)
            ->get();

        if ($files->isEmpty()) {
            $this->info('No stuck evidence files found.');

            return 0;
        }

        $count = 0;

        foreach ($files as $evidence) {
            $this->line("Resuming #{$evidence->id}: {$evidence->original_filename} (ai_analysis_status={$evidence->ai_analysis_status})");

            if ($this->option('sync')) {
                app(DirectEvidenceAnalysisService::class)->process($evidence);
                $this->info("  Done — status: {$evidence->fresh()->ai_analysis_status}");
            } else {
                AnalyzeEvidenceJob::dispatch($evidence->id);
            }

            $count++;
        }

        $this->info("Resumed {$count} evidence file(s).");

        return 0;
    }
}
