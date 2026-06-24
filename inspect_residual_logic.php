<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$spreadsheet = IOFactory::load('storage/app/imports/risk_register.xlsx');
$sheet = $spreadsheet->getSheetByName('Risk Register');
$rows = $sheet->toArray(null, true, true, true);

$formulas = [
    'res_tv * res_lh * 8' => function($t, $av, $tv, $lh, $resTv, $resLh) { return $resTv * $resLh * 8; },
    'av * tv * res_lh' => function($t, $av, $tv, $lh, $resTv, $resLh) { return $av * $tv * $resLh; },
    't * tv * res_lh' => function($t, $av, $tv, $lh, $resTv, $resLh) { return $t * $tv * $resLh; },
    'res_tv * res_lh * av' => function($t, $av, $tv, $lh, $resTv, $resLh) { return $resTv * $resLh * $av; },
    'res_tv * res_lh * t' => function($t, $av, $tv, $lh, $resTv, $resLh) { return $resTv * $resLh * $t; },
    'res_tv * res_lh * (tv/2)' => function($t, $av, $tv, $lh, $resTv, $resLh) { return $resTv * $resLh * ($tv / 2); },
    // What about: rating * (res_lh / lh) * (res_tv / tv) ?
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
