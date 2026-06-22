<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

echo "pci_dss_findings columns:\n";
print_r(Schema::getColumnListing('pci_dss_findings'));

echo "\niso_gap_assessments columns:\n";
print_r(Schema::getColumnListing('iso_gap_assessments'));

echo "\nSample pci_dss_findings row:\n";
print_r(DB::table('pci_dss_findings')->limit(1)->first());

echo "\nSample iso_gap_assessments row:\n";
print_r(DB::table('iso_gap_assessments')->limit(1)->first());
