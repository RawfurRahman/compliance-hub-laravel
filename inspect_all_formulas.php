<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$spreadsheet = IOFactory::load('storage/app/imports/risk_register.xlsx');
$sheet = $spreadsheet->getSheetByName('Risk Register');
$rows = $sheet->toArray(null, false, false, true); // do not calculate, do not format

$formulaCount = 0;
for ($r = 4; $r <= count($rows); $r++) {
    foreach ($rows[$r] as $col => $val) {
        if (is_string($val) && strpos($val, '=') === 0) {
            echo "Formula found at $col$r: $val\n";
            $formulaCount++;
        }
    }
}
echo "Total formulas in data rows: $formulaCount\n";
