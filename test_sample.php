<?php

use App\Models\EvaluationCorpusItem;
use App\Services\EvaluationSyntheticFileFactory;

require 'vendor/autoload.php';

$factory = new EvaluationSyntheticFileFactory;
$items = EvaluationCorpusItem::where('evidence_type', 'screenshot')
    ->whereNotNull('truth_rationale')
    ->first();

if ($items) {
    // Mirror production: evidence_summary only, never truth_rationale.
    $body = trim((string) ($items->evidence_summary ?? ''));
    $contents = $factory->generate($items->evidence_type, $items->frameworkControl->control_id ?? 'test', $body);
    $ext = $factory->extension($items->evidence_type);
    file_put_contents('/tmp/test_sample.'.$ext, $contents);

    echo 'Type: '.$items->evidence_type.PHP_EOL;
    echo 'Control: '.($items->frameworkControl->control_id ?? 'null').PHP_EOL;
    echo 'Size: '.strlen($contents).PHP_EOL;
    echo 'PNG start: '.(str_starts_with($contents, chr(0x89).'PNG') ? 'yes' : 'no').PHP_EOL;
    echo 'Contains compliant: '.(str_contains($contents, 'compliant') ? 'yes' : 'no').PHP_EOL;
    echo 'Contains partial: '.(str_contains($contents, 'partial') ? 'yes' : 'no').PHP_EOL;
    echo 'Contains non_compliant: '.(str_contains($contents, 'non_compliant') ? 'yes' : 'no').PHP_EOL;
    echo 'Rationale length: '.strlen($items->truth_rationale).PHP_EOL;
    echo 'Summary length: '.strlen($items->evidence_summary ?? 'null').PHP_EOL;
    echo PHP_EOL.'First 200 chars of body: '.substr($body, 0, 200).PHP_EOL;
    echo PHP_EOL.'First 200 chars of contents: '.substr($contents, 0, 200).PHP_EOL;
}
echo 'Direct test passed!'.PHP_EOL;
