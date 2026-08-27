<?php

use App\Models\PciDssRequirement;
use Illuminate\Contracts\Console\Kernel;

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$req = PciDssRequirement::first();
if ($req) {
    echo $req->toJson()."\n";
} else {
    echo "No PCI requirement found!\n";
}
