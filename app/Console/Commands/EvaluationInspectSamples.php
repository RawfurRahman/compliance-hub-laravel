<?php

namespace App\Console\Commands;

use App\Models\EvaluationCorpusItem;
use App\Services\EvaluationRunService;
use App\Services\EvaluationSyntheticFileFactory;
use Illuminate\Console\Command;

class EvaluationInspectSamples extends Command
{
    protected $signature = 'evaluation:inspect-samples {--out=storage/app/public/evaluation-samples}';

    protected $description = 'Inspect synthetic evidence files generated from the evaluation corpus';

    public function handle(EvaluationSyntheticFileFactory $factory, EvaluationRunService $runService): int
    {
        $outParam = $this->option('out');
        $outputDir = base_path($outParam);

        if (! is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $classLabels = ['compliant', 'partial', 'non_compliant'];
        $evidenceTypes = ['screenshot', 'diagram', 'policy_page', 'config_export', 'log_extract'];

        $samples = [];
        $hasLeak = false;

        foreach ($evidenceTypes as $type) {
            $items = EvaluationCorpusItem::where('evidence_type', $type)
                ->whereNotNull('truth_rationale')
                ->get();

            if ($items->isEmpty()) {
                $this->error("No corpus items found for evidence type: {$type}");

                continue;
            }

            // Select the item whose truth_rationale is longest
            $item = $items->sortByDesc(fn ($item) => strlen($item->truth_rationale ?? ''))->first();

            $controlId = $item->frameworkControl ? $item->frameworkControl->control_id : 'unknown';
            $groundTruth = $item->ground_truth ?? 'unknown';

            // Compose the body exactly as EvaluationRunService::dispatchItem() does:
            // evidence_summary only, never truth_rationale or expected_gaps.
            $body = $runService->evidenceBody($item);

            $contents = $factory->generate($type, $controlId, $body);

            $ext = $factory->extension($type);
            $filename = "{$type}_{$controlId}.{$ext}";
            $filepath = $outputDir.DIRECTORY_SEPARATOR.$filename;

            // Write each file to the output directory
            file_put_contents($filepath, $contents);

            // Leakage Assertion
            $fileLeaks = false;
            $leakDetails = [];

            foreach ($classLabels as $label) {
                if (stripos($contents, $label) !== false) {
                    $fileLeaks = true;
                    $leakDetails[] = "literal '$label'";
                }
            }

            if (is_array($item->expected_gaps)) {
                foreach ($item->expected_gaps as $gapItem) {
                    $gapText = is_array($gapItem) && isset($gapItem['gap']) ? $gapItem['gap'] : (is_string($gapItem) ? $gapItem : '');
                    if (trim($gapText) !== '' && stripos($contents, $gapText) !== false) {
                        $fileLeaks = true;
                        $leakDetails[] = "expected_gaps text '$gapText'";
                    }
                }
            }

            if ($fileLeaks) {
                $this->error("FAIL: {$filename} leaked ".implode(', ', $leakDetails));
                $hasLeak = true;
            } else {
                $this->info("PASS: {$filename}");
            }

            // Legibility Diagnostic for Images
            if (in_array($type, ['screenshot', 'diagram'])) {
                // Dimensions based on updated EvaluationSyntheticFileFactory
                $width = 1000;
                $fontSize = 16; // TTF font size 16pt
                $lineSpacing = (int) ($fontSize * 1.5);

                // Number of text lines rendered (wrapped at 60 chars)
                $wrapped = wordwrap($body, 60, "\n");
                $lines = count(explode("\n", $wrapped));

                // Calculate approximate height from bounding box
                $fontPath = resource_path('fonts/DejaVuSans.ttf');
                $bbox = imagettfbbox($fontSize, 0, $fontPath, 'A');
                $charHeight = abs($bbox[5] - $bbox[1]);

                // Canvas height dynamically calculated
                $textHeight = $lines * $lineSpacing;
                $diagramHeight = ($type === 'diagram') ? 200 : 0;
                $calculatedHeight = 100 + $textHeight + $diagramHeight + 50;
                $height = max(700, $calculatedHeight);

                $this->line("      [Diagnostic] Image: {$width}x{$height}px | Font: DejaVuSans {$fontSize}pt | Lines: {$lines} | Char Height: ~{$charHeight}px");
            }

            // Collect manifest data
            $samples[] = [
                'id' => $item->id,
                'evidence_type' => $type,
                'control_id' => $controlId,
                'ground_truth' => $groundTruth,
                'file_size' => strlen($contents),
                'body' => $body,
            ];
        }

        // Write manifest.txt
        $manifestLines = [];
        foreach ($samples as $s) {
            // Write each sample on a single line (JSON encoding the body to escape newlines)
            $manifestLines[] = sprintf(
                'item_id:%d | type:%s | control:%s | truth:%s | size:%d bytes | body:%s',
                $s['id'],
                $s['evidence_type'],
                $s['control_id'],
                $s['ground_truth'],
                $s['file_size'],
                json_encode($s['body'])
            );
        }

        file_put_contents($outputDir.DIRECTORY_SEPARATOR.'manifest.txt', implode("\n", $manifestLines));

        $this->info('Output path: '.realpath($outputDir));

        return $hasLeak ? 1 : 0;
    }
}
