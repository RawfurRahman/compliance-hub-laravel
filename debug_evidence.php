<?php

use App\Models\EvidenceFile;
use App\Models\FrameworkControl;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/vendor/autoload.php';

$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

// Get evidence files with framework_control_id not null
$files = EvidenceFile::whereNotNull('framework_control_id')->limit(5)->get(['id', 'framework_control_id', 'pci_dss_requirement_id', 'scan_status', 'project_id']);
echo "=== Evidence Files with framework_control_id (first 5) ===\n";
foreach ($files as $f) {
    echo "ID {$f->id}: fc_id={$f->framework_control_id}, pci_req={$f->pci_dss_requirement_id}, project_id={$f->project_id}, scan={$f->scan_status}\n";
}

// Get distinct framework_control_ids
$nonNullFC = EvidenceFile::whereNotNull('framework_control_id')->distinct()->pluck('framework_control_id');
echo "\n=== Distinct non-null framework_control_ids count: ".$nonNullFC->count()."\n";

// Get all framework controls
$fcs = FrameworkControl::all(['id', 'control_id', 'control_name', 'requirement_description']);
echo "\n=== All FrameworkControls ===\n";
foreach ($fcs as $fc) {
    echo "FC ID {$fc->id}: control_id=".var_export($fc->control_id, true).', control_name='.var_export($fc->control_name, true).', req_desc='.substr(var_export($fc->requirement_description, true), 0, 50)."\n";
}

// Check if any control_ids match the hardcoded patterns
echo "\n=== Checking control_id patterns ===\n";
foreach ($fcs as $fc) {
    $id = $fc->control_id;
    if ($id) {
        // Check if it matches A.X pattern or X pattern
        if (preg_match('/^A?\.?(\d+(\.\d+)?)$/', $id, $m)) {
            echo "FC {$fc->id}: control_id='$id' -> normalized: ".preg_replace('/^A\./i', '', $id)." (would match hardcoded)\n";
        } else {
            echo "FC {$fc->id}: control_id='$id' -> NO MATCH with hardcoded patterns\n";
        }
    } else {
        echo "FC {$fc->id}: control_id=NULL\n";
    }
}
