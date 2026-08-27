<?php

namespace App\Services;

use App\Models\EvaluationRunItem;
use Illuminate\Support\Facades\File;

/**
 * Exports evaluation run results to the system-output.csv summary report.
 */
class EvaluationCsvExporter
{
    public const DEFAULT_PATH = 'exports/system-output.csv';

    public const HEADERS = [
        'run_key',
        'item_order',
        'chapter',
        'framework_id',
        'framework_slug',
        'control_id',
        'evidence_type',
        'evidence_name',
        'ground_truth',
        'predicted_verdict',
        'ai_status',
        'verdict_match',
        'scan_status',
        'ai_analysis_status',
        'scan_ms',
        'analysis_ms',
        'total_ms',
        'gaps_count',
    ];

    public function export(string $runKey, string $outputPath = self::DEFAULT_PATH, array $metrics = []): string
    {
        $rows = EvaluationRunItem::query()
            ->with(['framework', 'evidenceFile'])
            ->where('run_key', $runKey)
            ->orderBy('item_order')
            ->get();

        $handle = fopen('php://temp', 'w+');
        fputcsv($handle, self::HEADERS);

        foreach ($rows as $row) {
            fputcsv($handle, [
                $row->run_key,
                $row->item_order,
                $row->chapter,
                $row->framework_id,
                $row->framework->slug ?? '',
                $row->control_id,
                $row->evidence_type,
                $row->evidence_name,
                $row->ground_truth,
                $row->predicted_verdict,
                $row->evidenceFile?->analysis_report_data['status'] ?? '',
                $row->verdict_match === null ? '' : ($row->verdict_match ? '1' : '0'),
                $row->scan_status,
                $row->ai_analysis_status,
                $row->scan_ms,
                $row->analysis_ms,
                $row->total_ms,
                $row->gaps_count,
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        $absolute = $this->absolutePath($outputPath);
        File::ensureDirectoryExists(dirname($absolute));
        File::put($absolute, $csv);

        if (! empty($metrics)) {
            $this->exportMetricsCsv($metrics, $absolute);
        }

        return $absolute;
    }

    private function exportMetricsCsv(array $metrics, string $csvPath): void
    {
        $metricsPath = $this->metricsPath($csvPath);

        $lines = [];

        // Confusion matrix section
        $lines[] = 'Confusion Matrix (actual/predicted: compliant / partial / non_compliant)';
        $lines[] = 'Actual,Predicted_Compliant,Predicted_Partial,Predicted_Non_Compliant';
        $classOrder = ['compliant', 'partial', 'non_compliant'];
        $confusion = $metrics['confusion'] ?? [];
        foreach ($classOrder as $actual) {
            $row = $confusion[$actual] ?? [];
            $c = $row['compliant'] ?? 0;
            $p = $row['partial'] ?? 0;
            $n = $row['non_compliant'] ?? 0;
            $lines[] = "{$actual},{$c},{$p},{$n}";
        }

        // Per-class metrics section
        $lines[] = '';
        $lines[] = 'Per-Class Metrics (one-vs-rest)';
        $lines[] = 'Class,Precision,Recall,F1';
        $perClass = $metrics['per_class'] ?? [];
        foreach ($classOrder as $class) {
            $mc = $perClass[$class] ?? [];
            $precision = $mc['precision'] ?? 0.0;
            $recall = $mc['recall'] ?? 0.0;
            $f1 = $mc['f1'] ?? 0.0;
            $lines[] = "{$class},{$precision},{$recall},{$f1}";
        }

        // Summary metrics section
        $lines[] = '';
        $lines[] = 'Summary Metrics';
        $lines[] = 'Metric,Value';
        $lines[] = 'macro_f1,'.($metrics['macro_f1'] ?? 0.0);
        $lines[] = 'accuracy,'.($metrics['accuracy'] ?? 0.0);
        $lines[] = 'parse_failure_rate,'.($metrics['parse_failure_rate'] ?? 0.0);
        $lines[] = 'critical_error_count,'.($metrics['critical_error_count'] ?? 0);

        $metricsContent = implode(PHP_EOL, $lines);
        File::put($metricsPath, $metricsContent);
    }

    private function metricsPath(string $csvPath): string
    {
        $dir = dirname($csvPath);

        return $dir.'/metrics.csv';
    }

    /**
     * Resolve a user-supplied output path. Absolute paths (Unix style or
     * Windows drive-letter style) are honored verbatim; anything else is
     * placed under the application exports directory.
     */
    private function absolutePath(string $outputPath): string
    {
        $trimmed = trim($outputPath);

        if ($trimmed !== '' && (str_starts_with($trimmed, '/') || preg_match('/^[A-Za-z]:[\\\\\\/]/', $trimmed))) {
            return $trimmed;
        }

        $relative = $trimmed === '' ? self::DEFAULT_PATH : ltrim($trimmed, '/\\');
        if (! str_starts_with($relative, 'exports/')) {
            $relative = 'exports/'.$relative;
        }

        return storage_path('app/private/'.$relative);
    }
}
