<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ProjectAssessment;
use App\Models\Framework;

echo "All project assessments in database:\n";
foreach (ProjectAssessment::with('framework', 'project')->get() as $pa) {
    $stats = $pa->stats();
    echo "- ID {$pa->id}: Project '{$pa->project->name}' (Type: {$pa->type}, Framework: {$pa->framework->slug}, Status: {$pa->overall_status})\n";
    echo "  Stats: total={$stats['total']}, compliant={$stats['compliant']}, nonCompliant={$stats['nonCompliant']}, compliancePct={$stats['compliancePct']}%\n";
}
