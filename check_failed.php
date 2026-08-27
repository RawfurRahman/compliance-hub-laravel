<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$failed = \App\Models\EvidenceFile::where('original_filename','like','XYZ_Bank%')
    ->where('ai_analysis_status','failed')
    ->get(['id','original_filename','ai_analysis_status','ai_observations','scan_status']);

echo "=== FAILED items ===\n";
foreach ($failed as $e) {
    echo "ID {$e->id} | {$e->original_filename} | scan={$e->scan_status} | obs=" . substr($e->ai_observations ?? '', 0, 200) . "\n\n";
}

$success = \App\Models\EvidenceFile::where('original_filename','like','XYZ_Bank%')
    ->where('ai_analysis_status','awaiting_review')
    ->count();
echo "\n=== awaiting_review count: {$success} ===\n";
