<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Distinct assessor_responses in pci_dss_findings:\n";
$pciResponses = DB::table('pci_dss_findings')->select('assessor_responses')->distinct()->pluck('assessor_responses')->toArray();
print_r($pciResponses);

echo "\nDistinct assessment_finding in pci_dss_findings:\n";
$pciFindings = DB::table('pci_dss_findings')->select('assessment_finding')->distinct()->pluck('assessment_finding')->toArray();
print_r($pciFindings);

echo "\nDistinct status in iso_gap_assessments:\n";
$isoStatus = DB::table('iso_gap_assessments')->select('status')->distinct()->pluck('status')->toArray();
print_r($isoStatus);

echo "\nDistinct risk_rating in iso_gap_assessments:\n";
$isoRisk = DB::table('iso_gap_assessments')->select('risk_rating')->distinct()->pluck('risk_rating')->toArray();
print_r($isoRisk);
