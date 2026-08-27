<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\Framework;
use Illuminate\Contracts\Console\Kernel;

foreach (Framework::all() as $f) {
    echo "- ID: {$f->id}, Slug: '{$f->slug}', Name: '{$f->name}', Active: ".($f->is_active ? 'Yes' : 'No')."\n";
}
