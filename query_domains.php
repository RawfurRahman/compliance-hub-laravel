<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$domains = \App\Models\FrameworkControl::select('domain')->distinct()->pluck('domain');
echo "UNIQUE DOMAINS:\n";
foreach ($domains as $d) {
    echo "- " . $d . "\n";
}
