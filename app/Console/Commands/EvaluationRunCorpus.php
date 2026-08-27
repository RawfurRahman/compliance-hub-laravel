<?php

namespace App\Console\Commands;

use App\Services\EvaluationRunService;
use Illuminate\Console\Command;

/**
 * Runs the evaluation corpus through the real ClamAV + Ollama evidence
 * pipeline, records per-stage latencies, and exports the system-output.csv
 * summary report for Chapter 6/7 reporting.
 *
 * Run:
 *   php artisan evaluation:run-corpus                  (full 60-item corpus)
 *   php artisan evaluation:run-corpus --baseline       (20-item manual baseline arm)
 *   php artisan evaluation:run-corpus --framework=pci_dss
 *   php artisan evaluation:run-corpus --chapter=chapter_6 --sync
 */
class EvaluationRunCorpus extends Command
{
    protected $signature = 'evaluation:run-corpus
        {--framework= : Limit to one framework slug (pci_dss|iso_27001|bb_ict|hitrust)}
        {--chapter= : Limit to one chapter (chapter_6|chapter_7)}
        {--baseline : Run the 20-item manual baseline arm (first 20 corpus items)}
        {--limit= : Maximum number of corpus items to process}
        {--offset= : Skip the first N corpus items}
        {--sync : Process each item inline instead of dispatching queued jobs}
        {--timeout=600 : Max seconds to wait for queued analysis to complete}
        {--poll=2 : Sweep poll interval in seconds}
        {--output= : CSV export path (default: storage/app/private/exports/system-output.csv)}';

    protected $description = 'Run the evaluation corpus through the secure evidence pipeline and export metrics';

    public function handle(EvaluationRunService $service): int
    {
        $startedAt = now();

        try {
            $this->validateOptions();
            $summary = $service->run($this->options(), function (string $message) {
                $this->line($message);
            });
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return Command::FAILURE;
        }

        $this->newLine();
        $this->info('=== Evaluation run complete ===');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Run key', $summary['run_key']],
                ['Mode', $summary['mode']],
                ['Items', $summary['total']],
                ['Stuck items', $summary['stuck']],
                ['Verdict matches', $summary['verdict_matches']],
                ['Accuracy (%)', $summary['accuracy']],
                ['Avg scan (ms)', $summary['avg_scan_ms']],
                ['Avg analysis (ms)', $summary['avg_analysis_ms']],
                ['Avg total (ms)', $summary['avg_total_ms']],
                ['Wall time (s)', $summary['wall_seconds']],
                ['Started', $startedAt->toDateTimeString()],
                ['CSV', $summary['csv_path']],
                ['Metrics CSV', dirname($summary['csv_path']).'/metrics.csv'],
            ]
        );

        $this->printBreakdown('Items per framework', $summary['framework_counts']);
        $this->printBreakdown('Items per chapter', $summary['chapter_counts']);
        $this->printBreakdown('Predicted verdicts', $summary['verdict_counts']);

        // Print confusion matrix
        $this->newLine();
        $this->info('=== Confusion Matrix ===');
        $this->table(
            ['Actual / Predicted', 'Compliant', 'Partial', 'Non_Compliant'],
            [
                ['Compliant', $summary['confusion']['compliant']['compliant'] ?? 0, $summary['confusion']['compliant']['partial'] ?? 0, $summary['confusion']['compliant']['non_compliant'] ?? 0],
                ['Partial', $summary['confusion']['partial']['compliant'] ?? 0, $summary['confusion']['partial']['partial'] ?? 0, $summary['confusion']['partial']['non_compliant'] ?? 0],
                ['Non_Compliant', $summary['confusion']['non_compliant']['compliant'] ?? 0, $summary['confusion']['non_compliant']['partial'] ?? 0, $summary['confusion']['non_compliant']['non_compliant'] ?? 0],
            ]
        );

        // Print per-class metrics
        $this->newLine();
        $this->info('=== Per-Class Metrics (one-vs-rest) ===');
        $this->table(
            ['Class', 'Precision', 'Recall', 'F1'],
            [
                ['Compliant', $summary['per_class']['compliant']['precision'] ?? 0.0, $summary['per_class']['compliant']['recall'] ?? 0.0, $summary['per_class']['compliant']['f1'] ?? 0.0],
                ['Partial', $summary['per_class']['partial']['precision'] ?? 0.0, $summary['per_class']['partial']['recall'] ?? 0.0, $summary['per_class']['partial']['f1'] ?? 0.0],
                ['Non_Compliant', $summary['per_class']['non_compliant']['precision'] ?? 0.0, $summary['per_class']['non_compliant']['recall'] ?? 0.0, $summary['per_class']['non_compliant']['f1'] ?? 0.0],
            ]
        );

        // Print summary metrics
        $this->newLine();
        $this->info('=== Summary Metrics ===');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Macro F1', $summary['macro_f1'] ?? 0.0],
                ['Accuracy', $summary['accuracy'] ?? 0.0],
                ['Parse Failure Rate (%)', $summary['parse_failure_rate'] ?? 0.0],
                ['Critical Error Count', $summary['critical_error_count'] ?? 0],
                ['Latency (scan_ms mean/ms)', $summary['latency']['scan_ms']['mean'] ?? 0 .'/'.($summary['latency']['scan_ms']['median'] ?? 0)],
                ['Latency (analysis_ms mean/ms)', $summary['latency']['analysis_ms']['mean'] ?? 0 .'/'.($summary['latency']['analysis_ms']['median'] ?? 0)],
                ['Latency (total_ms mean/ms)', $summary['latency']['total_ms']['mean'] ?? 0 .'/'.($summary['latency']['total_ms']['median'] ?? 0)],
            ]
        );

        if ($summary['stuck'] > 0) {
            $this->warn('WARNING: '.$summary['stuck'].' item(s) were force-finalized as failed (see --timeout/--sync).');

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function validateOptions(): void
    {
        if ($this->option('framework') !== null
            && ! in_array($this->option('framework'), ['pci_dss', 'iso_27001', 'bb_ict', 'hitrust'], true)) {
            throw new \InvalidArgumentException('--framework must be one of: pci_dss, iso_27001, bb_ict, hitrust.');
        }

        if ($this->option('chapter') !== null
            && ! in_array($this->option('chapter'), ['chapter_6', 'chapter_7'], true)) {
            throw new \InvalidArgumentException('--chapter must be chapter_6 or chapter_7.');
        }
    }

    private function printBreakdown(string $title, array $counts): void
    {
        if (empty($counts)) {
            return;
        }

        $this->newLine();
        $this->info('=== '.$title.' ===');

        foreach ($counts as $label => $count) {
            $this->line(sprintf('  %-20s %d', $label, $count));
        }
    }
}
