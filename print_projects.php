<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Project;

foreach (Project::all() as $p) {
    echo "- ID: {$p->id}, Name: '{$p->name}', Module Type: '{$p->module_type}'\n";
}
