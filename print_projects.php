<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\Project;
use Illuminate\Contracts\Console\Kernel;

foreach (Project::all() as $p) {
    echo "- ID: {$p->id}, Name: '{$p->name}', Module Type: '{$p->module_type}'\n";
}
