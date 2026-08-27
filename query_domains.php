<?php

use App\Models\FrameworkControl;
use Illuminate\Contracts\Console\Kernel;

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$domains = FrameworkControl::select('domain')->distinct()->pluck('domain');
echo "UNIQUE DOMAINS:\n";
foreach ($domains as $d) {
    echo '- '.$d."\n";
}
