<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$spreadsheet = IOFactory::load('storage/app/imports/risk_register.xlsx');
$sheet = $spreadsheet->getSheetByName('Risk Register');
$rows = $sheet->toArray(null, true, true, true);

$formulas = [
    'av * tv * res_lh' => function($t, $av, $tv, $lh, $resTv, $resLh) { return $av * $tv * $resLh; },
    'res_tv * tv * res_lh' => function($t, $av, $tv, $lh, $resTv, $resLh) { return $resTv * $tv * $resLh; },
    'res_tv * av * res_lh' => function($t, $av, $tv, $lh, $resTv, $resLh) { return $resTv * $av * $resLh; },
    'res_tv * t * res_lh' => function($t, $av, $tv, $lh, $resTv, $resLh) { return $resTv * $t * $resLh; },
    'res_tv * tv * lh' => function($t, $av, $tv, $lh, $resTv, $resLh) { return $resTv * $tv * $lh; },
    'res_tv * res_tv * res_lh' => function($t, $av, $tv, $lh, $resTv, $resLh) { return $resTv * $resTv * $resLh; },
];

foreach ($formulas as $name => $f) {
    $matches = 0;
    $total = 0;
    for ($r = 4; $r <= count($rows); $r++) {
        $row = $rows[$r];
        $serialNo = isset($row['A']) ? trim($row['A']) : '';
        if (empty($serialNo)) continue;

        $t = intval($row['G']);
        $av = intval($row['M']);
        $tv = intval($row['N']);
        $lh = intval($row['O']);
        $resTv = intval($row['W']);
        $resLh = intval($row['X']);
        $sheetRating = intval($row['Y']);

        $calc = $f($t, $av, $tv, $lh, $resTv, $resLh);
        $total++;
        if ($calc === $sheetRating) {
            $matches++;
        }
    }
    echo "Formula '$name': $matches / $total matches\n";
}
