<?php

namespace App\Modules\RiskManagement\Console\Commands;

use App\Models\EvidenceFile;
use App\Modules\RiskManagement\Services\DirectEvidenceAnalysisService;
use Illuminate\Console\Command;

class ProcessEvidenceAnalysis extends Command
{
    protected $signature = 'rmm:process-evidence {id? : Evidence file ID to process}
        {--all : Process all evidence files with pending analysis}';
    protected $description = 'Run AI analysis on evidence files directly (bypasses n8n)';

    public function handle(DirectEvidenceAnalysisService $analysisService): int
    {
        $query = EvidenceFile::whereIn('ai_analysis_status', ['pending', 'failed']);

        if ($id = $this->argument('id')) {
            $query->where('id', $id);
        } elseif (!$this->option('all')) {
            $this->error('Specify an ID or use --all to process all pending files.');
            return 1;
        }

        $files = $query->get();

        if ($files->isEmpty()) {
            $this->info('No pending evidence files found.');
            return 0;
        }

        $count = 0;
        foreach ($files as $evidence) {
            $this->line("Processing evidence #{$evidence->id}: {$evidence->original_filename}");
            $analysisService->process($evidence);
            $this->info("  Done — status: {$evidence->fresh()->ai_analysis_status}");
            $count++;
        }

        $this->info("Processed {$count} evidence file(s).");
        return 0;
    }
}
