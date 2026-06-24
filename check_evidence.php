<?php
$db = new PDO('sqlite:database/database.sqlite');
$rows = $db->query('SELECT id, original_filename, mime_type, scan_status, ai_analysis_status, ai_observations, ai_recommendations, created_at FROM evidence_files ORDER BY id DESC LIMIT 10');
foreach ($rows as $row) {
    echo json_encode($row, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL . '---' . PHP_EOL;
}
