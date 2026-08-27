<?php

$data = file_get_contents('store.json');
$json = json_decode($data, true);
echo json_encode($json['domains']);
