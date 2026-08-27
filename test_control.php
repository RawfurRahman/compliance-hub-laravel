<?php

use App\Models\FrameworkControl;

require 'vendor/autoload.php';

$fc = new FrameworkControl;
$fc->control_id = 'Section 5.1';
$fc->requirement_description = 'Some description: Test';
echo 'Test 1 - Section 5.1: '.$fc->getControlNameAttribute().PHP_EOL;

$fc2 = new FrameworkControl;
$fc2->control_id = '5.1';
$fc2->requirement_description = 'Some description: Test';
echo 'Test 2 - 5.1: '.$fc2->getControlNameAttribute().PHP_EOL;

$fc3 = new FrameworkControl;
$fc3->control_id = '01.a';
$fc3->requirement_description = 'Some description: Test';
echo 'Test 3 - 01.a: '.$fc3->getControlNameAttribute().PHP_EOL;

$fc4 = new FrameworkControl;
$fc4->control_id = '5.4.1';
$fc4->requirement_description = 'Some description: Test';
echo 'Test 4 - 5.4.1: '.$fc4->getControlNameAttribute().PHP_EOL;

$fc5 = new FrameworkControl;
$fc5->control_id = 'Section 8.3';
$fc5->requirement_description = 'Some description: Test';
echo 'Test 5 - Section 8.3: '.$fc5->getControlNameAttribute().PHP_EOL;

$fc6 = new FrameworkControl;
$fc6->control_id = 'Section 5.2.1';
$fc6->requirement_description = 'Some description: Test';
echo 'Test 6 - Section 5.2.1: '.$fc6->getControlNameAttribute().PHP_EOL;
