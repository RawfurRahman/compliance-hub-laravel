<?php

$html = file_get_contents('http://127.0.0.1:8000/evidence/7');
$pos = strpos($html, 'integrity-hub-data');
if ($pos !== false) {
    echo substr($html, $pos, 500);
} else {
    echo 'Not found';
}
