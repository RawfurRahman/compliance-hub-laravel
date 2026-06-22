<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Projects:\n";
print_r(DB::table('projects')->get()->toArray());

if (Schema::hasTable('pci_gap_assessments')) {
    echo "\npci_gap_assessments count: " . DB::table('pci_gap_assessments')->count() . "\n";
    print_r(DB::table('pci_gap_assessments')->get()->toArray());
}

if (Schema::hasTable('iso_gap_assessments')) {
    echo "\niso_gap_assessments count: " . DB::table('iso_gap_assessments')->count() . "\n";
    print_r(DB::table('iso_gap_assessments')->get()->toArray());
}
