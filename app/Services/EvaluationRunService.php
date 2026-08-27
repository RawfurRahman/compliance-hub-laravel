<?php

namespace App\Services;

use App\Jobs\AnalyzeEvidenceJob;
use App\Models\EvaluationCorpusItem;
use App\Models\EvaluationRunItem;
use App\Models\EvidenceFile;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * Orchestrates an evaluation corpus run: materializes synthetic evidence,
 * pushes each file through the normal ClamAV + Ollama pipeline, records
 * per-stage latencies, guarantees no item is left incomplete, derives verdict
 * metrics, and exports the system-output.csv summary.
 */
class EvaluationRunService
{
    private const TERMINAL_AI_STATUSES = [
        'awaiting_review',
        'completed',
        'approved',
        'failed',
        'skipped_due_to_scan',
    ];

    public function __construct(
        private EvaluationSyntheticFileFactory $fileFactory,
        private EvaluationCsvExporter $csvExporter,
        private DirectEvidenceAnalysisService $analysisService,
    ) {}

    /**
     * @param  array<string, mixed>  $options
     * @param  callable(string):void|null  $output
     */
    public function run(array $options, ?callable $output = null): array
    {
        $line = $output ?? static fn (string $message) => null;

        $sync = (bool) ($options['sync'] ?? false);
        $timeout = (int) ($options['timeout'] ?? 600);
        $poll = (int) ($options['poll'] ?? 2);
        $runKey = 'eval_'.now()->format('Ymd_His').'_'.($sync ? 'sync' : 'queue');

        $project = $this->ensureProject();
        $user = $this->ensureUser();

        $corpusItems = $this->selectCorpusItems($options);
        if ($corpusItems->isEmpty()) {
            throw new \RuntimeException('No evaluation corpus items matched the requested subset.');
        }

        $started = microtime(true);
        $evidenceFiles = [];

        foreach ($corpusItems as $order => $item) {
            $position = $order + 1;
            $evidence = $this->dispatchItem($item, $project, $user, $runKey, $position, $sync);
            $evidenceFiles[] = $evidence;
            $line("[{$position}/{$corpusItems->count()}] {$item->evidence_name} -> #{$evidence->id} ({$evidence->ai_analysis_status})");
        }

        if (! $sync) {
            $this->waitForCompletion($evidenceFiles, $timeout, $poll, $line);
        }

        $this->recordRunItems($runKey, $corpusItems, $evidenceFiles);

        $metrics = $this->metrics($runKey);

        $csvPath = $this->csvExporter->export(
            $runKey,
            (string) ($options['output'] ?? EvaluationCsvExporter::DEFAULT_PATH),
            $metrics
        );

        return $this->summary($runKey, $started, $sync, $csvPath);
    }

    /**
     * Select the corpus items for the requested subset.
     *
     * @param  array<string, mixed>  $options
     */
    private function selectCorpusItems(array $options): Collection
    {
        $query = EvaluationCorpusItem::query()->with('frameworkControl');

        if (($options['baseline'] ?? false)) {
            $options['offset'] = $options['offset'] ?? 0;
            $options['limit'] = $options['limit'] ?? 20;
        }

        if (! empty($options['chapter'])) {
            $query->where('chapter', $options['chapter']);
        }

        if (! empty($options['framework'])) {
            $query->whereHas('frameworkControl.framework', fn ($q) => $q->where('slug', $options['framework']));
        }

        $query->orderBy('id');

        if (isset($options['offset']) && (int) $options['offset'] > 0) {
            $query->skip((int) $options['offset']);
        }

        if (isset($options['limit']) && (int) $options['limit'] > 0) {
            $query->limit((int) $options['limit']);
        }

        return $query->get();
    }

    /**
     * The model-visible text body for a corpus item.
     *
     * Only evidence_summary is exposed to the pipeline. ground_truth,
     * truth_rationale and expected_gaps are answer-key fields and must
     * never be embedded into generated evidence files.
     */
    public function evidenceBody(EvaluationCorpusItem $item): string
    {
        return trim((string) $item->evidence_summary);
    }

    /**
     * Materialize a synthetic file and push it through the secure pipeline.
     */
    private function dispatchItem(
        EvaluationCorpusItem $item,
        Project $project,
        User $user,
        string $runKey,
        int $order,
        bool $sync,
    ): EvidenceFile {
        $contents = $this->fileFactory->generate(
            $item->evidence_type,
            $item->frameworkControl->control_id,
            $this->evidenceBody($item)
        );

        $path = sprintf(
            'mock/evaluation/%s/%03d_%s.%s',
            $runKey,
            $order,
            $this->fileSlug($item->frameworkControl->control_id),
            $this->fileFactory->extension($item->evidence_type)
        );

        Storage::disk('public')->put($path, $contents);

        $evidence = $project->evidenceFiles()->create([
            'framework_control_id' => $item->framework_control_id,
            'user_id' => $user->id,
            'file_path' => $path,
            'original_filename' => $item->evidence_name,
            'mime_type' => $this->fileFactory->mimeType($item->evidence_type),
            'scan_status' => 'pending',
            'ai_analysis_status' => 'pending',
        ]);

        if ($sync) {
            $this->analysisService->process($evidence);

            return $evidence->fresh();
        }

        AnalyzeEvidenceJob::dispatch($evidence->id);

        return $evidence;
    }

    /**
     * Poll until every queued item reaches a terminal state or the deadline
     * elapses, force-finalizing any stragglers so nothing remains stuck
     * (mirrors Tasks B4/B6/B7).
     *
     * @param  EvidenceFile[]  $evidenceFiles
     * @param  callable(string):void  $line
     */
    private function waitForCompletion(array $evidenceFiles, int $timeout, int $poll, callable $line): void
    {
        $ids = collect($evidenceFiles)->pluck('id')->all();
        $deadline = now()->addSeconds($timeout);

        while (true) {
            $current = EvidenceFile::whereIn('id', $ids)->get();
            $pending = $this->pendingFiles($current);

            if ($pending->isEmpty() || now()->gte($deadline)) {
                break;
            }

            $line('  waiting on '.$pending->count().' queued item(s)...');
            sleep(max(1, $poll));
        }

        $current = EvidenceFile::whereIn('id', $ids)->get();
        foreach ($this->pendingFiles($current) as $evidence) {
            $line("  force-finalizing stuck item #{$evidence->id} ({$evidence->ai_analysis_status})");
            $evidence->update([
                'ai_analysis_status' => 'failed',
                'scan_status' => $evidence->scan_status === 'processing' ? 'failed' : $evidence->scan_status,
                'ai_observations' => 'Evaluation run timed out before queued analysis completed.',
                'ai_recommendations' => 'Re-run with --sync, start a queue worker, or increase --timeout.',
                'ai_gaps' => [],
            ]);
        }
    }

    /**
     * @param  Collection<int, EvidenceFile>|EvidenceFile[]  $evidenceFiles
     */
    private function pendingFiles(Collection|array $evidenceFiles): Collection
    {
        return collect($evidenceFiles)
            ->filter(fn (EvidenceFile $e) => ! in_array($e->ai_analysis_status, self::TERMINAL_AI_STATUSES, true))
            ->values();
    }

    /**
     * Build the per-item evaluation_run_items rows with verdict metrics.
     *
     * @param  Collection<int, EvaluationCorpusItem>  $corpusItems
     * @param  EvidenceFile[]  $evidenceFiles
     */
    private function recordRunItems(string $runKey, Collection $corpusItems, array $evidenceFiles): void
    {
        foreach ($corpusItems->values() as $order => $item) {
            $evidence = $evidenceFiles[$order] ?? null;

            if (! $evidence) {
                continue;
            }

            $evidence = $evidence->fresh();
            $gaps = $evidence->ai_gaps ?? [];
            $predicted = $this->resolveVerdict($evidence, $gaps);

            EvaluationRunItem::updateOrCreate(
                [
                    'run_key' => $runKey,
                    'evaluation_corpus_item_id' => $item->id,
                ],
                [
                    'item_order' => $order + 1,
                    'evidence_file_id' => $evidence->id,
                    'framework_id' => $item->frameworkControl->framework_id,
                    'framework_control_id' => $item->framework_control_id,
                    'chapter' => $item->chapter,
                    'control_id' => $item->frameworkControl->control_id,
                    'evidence_type' => $item->evidence_type,
                    'evidence_name' => $item->evidence_name,
                    'ground_truth' => $item->ground_truth,
                    'predicted_verdict' => $predicted,
                    'verdict_match' => $predicted === $item->ground_truth,
                    'scan_ms' => $evidence->scan_ms,
                    'analysis_ms' => $evidence->analysis_ms,
                    'total_ms' => $evidence->total_ms,
                    'scan_status' => $evidence->scan_status,
                    'ai_analysis_status' => $evidence->ai_analysis_status,
                    'gaps_count' => count($gaps),
                ]
            );
        }
    }

    /**
     * Resolve the predicted verdict for an evaluation item.
     *
     * The benchmark classifier is the model's own three-class status field
     * returned by the analysis prompt (DirectEvidenceAnalysisService stores it
     * in analysis_report_data.status). Only when the response carried no usable
     * status — e.g. an unparseable reply, already tracked via parse_failure_rate
     * — does the legacy gap-derived heuristic apply as a fallback.
     */
    private function resolveVerdict(EvidenceFile $evidence, array $gaps): string
    {
        $status = $evidence->analysis_report_data['status'] ?? null;

        if (is_string($status) && in_array($status, EvaluationCorpusItem::GROUND_TRUTHS, true)) {
            return $status;
        }

        return $this->predictVerdict($gaps);
    }

    /**
     * Legacy gap-derived verdict heuristic, used only when the model response
     * did not contain an explicit three-class status (e.g. JSON parse failure).
     *
     * @param  array<int, mixed>  $gaps
     */
    public function predictVerdict(array $gaps): string
    {
        if (empty($gaps)) {
            return 'compliant';
        }

        $high = collect($gaps)->pluck('severity')->contains(
            fn ($severity) => in_array(strtolower((string) $severity), ['high', 'critical'], true)
        );

        if (count($gaps) >= 3 || $high) {
            return 'non_compliant';
        }

        return 'partial';
    }

    /**
     * Produce the run summary consumed by the command's reporters.
     *
     * @return array{run_key, mode, total, stuck, verdict_matches, accuracy,
     *   avg_scan_ms, avg_analysis_ms, avg_total_ms, wall_seconds, csv_path,
     *   framework_counts, chapter_counts, verdict_counts, confusion, per_class,
     *   macro_f1, accuracy, parse_failure_rate, latency, critical_error_count}
     */
    private function summary(
        string $runKey,
        float $started,
        bool $sync,
        string $csvPath,
    ): array {
        $items = EvaluationRunItem::where('run_key', $runKey)->get();

        $matches = $items->filter(fn (EvaluationRunItem $i) => $i->verdict_match === true)->count();
        $total = $items->count();

        $avg = static fn (string $col) => $items->whereNotNull($col)->isNotEmpty()
            ? (int) round($items->whereNotNull($col)->avg($col))
            : 0;

        $metrics = $this->metrics($runKey);

        return array_merge([
            'run_key' => $runKey,
            'mode' => $sync ? 'sync' : 'queued',
            'total' => $total,
            'stuck' => $items->filter(
                fn (EvaluationRunItem $i) => ! in_array($i->ai_analysis_status, self::TERMINAL_AI_STATUSES, true)
            )->count(),
            'verdict_matches' => $matches,
            'accuracy' => $total > 0 ? round(($matches / $total) * 100, 2) : 0.0,
            'avg_scan_ms' => $avg('scan_ms'),
            'avg_analysis_ms' => $avg('analysis_ms'),
            'avg_total_ms' => $avg('total_ms'),
            'wall_seconds' => round(microtime(true) - $started, 2),
            'csv_path' => $csvPath,
            'framework_counts' => $items->groupBy(fn ($i) => $i->framework_id)
                ->map(fn ($group) => $group->count())
                ->sortDesc()
                ->toArray(),
            'chapter_counts' => $items->groupBy('chapter')
                ->map->count()
                ->sortDesc()
                ->toArray(),
            'verdict_counts' => $items->groupBy('predicted_verdict')
                ->map->count()
                ->sortDesc()
                ->toArray(),
        ], $metrics);
    }

    /**
     * Compute per-run metrics over evaluation_run_items for the given run key.
     *
     * @return array{confusion: array{compliant, partial, non_compliant}[},
     *   per_class: array{compliant: {precision, recall, f1}, partial: {precision, recall, f1},
     *     non_compliant: {precision, recall, f1}}, macro_f1, accuracy,
     *   parse_failure_rate, latency: {scan_ms, analysis_ms, total_ms: {mean, median}},
     *   critical_error_count}
     */
    public function metrics(string $runKey): array
    {
        $items = EvaluationRunItem::where('run_key', $runKey)->get();

        $classLabels = ['compliant', 'partial', 'non_compliant'];

        // --- confusion matrix 3x3 [actual][predicted] ---
        $confusion = [];
        foreach ($classLabels as $actual) {
            $confusion[$actual] = [];
            foreach ($classLabels as $predicted) {
                $confusion[$actual][$predicted] = $items->filter(
                    fn (EvaluationRunItem $i) => $i->ground_truth === $actual && $i->predicted_verdict === $predicted
                )->count();
            }
        }

        // --- per-class one-vs-rest precision, recall, f1 ---
        $perClass = [];
        foreach ($classLabels as $class) {
            $tp = $items->where('ground_truth', $class)->where('predicted_verdict', $class)->count();
            $fp = $items->where('predicted_verdict', $class)->whereNotNull('ground_truth')->where('ground_truth', '!=', $class)->count();
            $fn = $items->where('ground_truth', $class)->whereNotIn('predicted_verdict', [$class])->count();

            $precision = ($tp + $fp > 0) ? round($tp / ($tp + $fp), 2) : 0.0;
            $recall = ($tp + $fn > 0) ? round($tp / ($tp + $fn), 2) : 0.0;
            $f1 = ($precision + $recall > 0) ? round(2 * ($precision * $recall) / ($precision + $recall), 2) : 0.0;

            $perClass[$class] = ['precision' => $precision, 'recall' => $recall, 'f1' => $f1];
        }

        // --- macro_f1 ---
        $f1Values = array_values($perClass);
        $f1Sum = array_sum(array_column($f1Values, 'f1'));
        $macroF1 = count($f1Values) > 0 ? round($f1Sum / count($f1Values), 2) : 0.0;

        // --- accuracy (unchanged from current calculation) ---
        $matches = $items->filter(fn ($i) => $i->verdict_match === true)->count();
        $accuracy = $items->count() > 0 ? round(($matches / $items->count()) * 100, 2) : 0.0;

        // --- parse_failure_rate: percentage of evidence files with ai_parse_fallback true ---
        $parseFailures = 0;
        $parseTotal = 0;
        foreach ($items as $item) {
            $evidence = $item->evidenceFile;
            if ($evidence) {
                $parseTotal++;
                if ($evidence->ai_parse_fallback) {
                    $parseFailures++;
                }
            }
        }
        $parseFailureRate = $parseTotal > 0 ? round(($parseFailures / $parseTotal) * 100, 2) : 0.0;

        // --- latency: mean and median of scan_ms, analysis_ms and total_ms ---
        $scanValues = $items->whereNotNull('scan_ms')->pluck('scan_ms')->all();
        $analysisValues = $items->whereNotNull('analysis_ms')->pluck('analysis_ms')->all();
        $totalValues = $items->whereNotNull('total_ms')->pluck('total_ms')->all();

        $latency = [
            'scan_ms' => [
                'mean' => $scanValues ? round(array_sum($scanValues) / count($scanValues)) : 0,
                'median' => $scanValues ? round($this->median($scanValues)) : 0,
            ],
            'analysis_ms' => [
                'mean' => $analysisValues ? round(array_sum($analysisValues) / count($analysisValues)) : 0,
                'median' => $analysisValues ? round($this->median($analysisValues)) : 0,
            ],
            'total_ms' => [
                'mean' => $totalValues ? round(array_sum($totalValues) / count($totalValues)) : 0,
                'median' => $totalValues ? round($this->median($totalValues)) : 0,
            ],
        ];

        // --- critical_error_count: ground_truth is non_compliant but predicted_verdict is compliant ---
        $criticalErrorCount = $items->where('ground_truth', 'non_compliant')->where('predicted_verdict', 'compliant')->count();

        return [
            'confusion' => $confusion,
            'per_class' => $perClass,
            'macro_f1' => $macroF1,
            'accuracy' => $accuracy,
            'parse_failure_rate' => $parseFailureRate,
            'latency' => $latency,
            'critical_error_count' => $criticalErrorCount,
        ];
    }

    private function median(array $values): float
    {
        $count = count($values);
        if ($count === 0) {
            return 0;
        }
        sort($values);
        $mid = (int) floor($count / 2);
        if ($count % 2 === 0) {
            return ($values[$mid - 1] + $values[$mid]) / 2.0;
        }

        return $values[$mid];
    }

    private function ensureProject(): Project
    {
        return Project::firstOrCreate(
            ['name' => 'Evaluation Corpus Runner'],
            ['module_type' => 'evaluation', 'user_id' => $this->ensureUser()->id]
        );
    }

    private function ensureUser(): User
    {
        return User::first() ?? User::create([
            'username' => 'system-eval',
            'email' => 'system-eval@compliancehub.test',
            'password' => 'system-eval-corpus',
        ]);
    }

    private function fileSlug(string $controlId): string
    {
        $slug = strtolower(trim($controlId));
        $slug = preg_replace('/[^a-z0-9]+/', '_', $slug) ?? $slug;

        return trim($slug, '_');
    }
}
