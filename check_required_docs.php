<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

foreach (['required_document_lists', 'required_documents', 'evidence_files'] as $table) {
    if (Schema::hasTable($table)) {
        echo "Table '$table': ".DB::table($table)->count()." rows\n";
        if (DB::table($table)->count() > 0) {
            print_r(DB::table($table)->limit(3)->get()->toArray());
        }
    } else {
        echo "Table '$table' does NOT exist\n";
    }
}
