<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$req = \App\Models\PciDssRequirement::first();
if ($req) {
    echo $req->toJson() . "\n";
} else {
    echo "No PCI requirement found!\n";
}
