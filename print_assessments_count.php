<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

foreach (['pci_gap_assessments', 'iso_gap_assessments', 'pci_dss_findings', 'pci_dss_detail_findings'] as $table) {
    if (Schema::hasTable($table)) {
        echo "Table '$table': " . DB::table($table)->count() . " rows\n";
    } else {
        echo "Table '$table' does NOT exist\n";
    }
}
