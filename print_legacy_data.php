<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "pci_dss_findings count: " . DB::table('pci_dss_findings')->count() . "\n";
echo "pci_dss_findings non-empty assessment_finding count: " . DB::table('pci_dss_findings')->whereNotNull('assessment_finding')->where('assessment_finding', '!=', '')->count() . "\n";
$samplePci = DB::table('pci_dss_findings')->whereNotNull('assessment_finding')->where('assessment_finding', '!=', '')->first();
if ($samplePci) {
    print_r($samplePci);
} else {
    echo "No non-empty PCI findings found.\n";
    // Check first 5 rows
    print_r(DB::table('pci_dss_findings')->limit(5)->get()->toArray());
}

echo "\niso_gap_assessments count: " . DB::table('iso_gap_assessments')->count() . "\n";
echo "iso_gap_assessments non-open status count: " . DB::table('iso_gap_assessments')->where('status', '!=', 'Open')->count() . "\n";
$sampleIso = DB::table('iso_gap_assessments')->where('status', '!=', 'Open')->first();
if ($sampleIso) {
    print_r($sampleIso);
} else {
    echo "No non-Open ISO assessments found.\n";
}
