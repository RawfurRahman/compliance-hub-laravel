<?php

$html = file_get_contents('output_evidence_7.html');
$start = strpos($html, 'function premiumEvidenceWorkspace');
$end = strpos($html, '</script>', $start);
echo substr($html, $start, $end - $start);
