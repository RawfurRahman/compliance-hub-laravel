<?php

$html = file_get_contents('output_evidence_7.html');
$pos = strpos($html, '<script id="integrity-hub-data" type="application/json">');
if ($pos !== false) {
    $end = strpos($html, '</script>', $pos);
    file_put_contents('store.json', substr($html, $pos + 56, $end - $pos - 56));
    echo "Saved to store.json\n";
} else {
    echo "Not found in HTML\n";
}
