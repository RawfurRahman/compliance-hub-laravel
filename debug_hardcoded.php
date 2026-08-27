<?php

use App\Models\FrameworkControl;

require 'vendor/autoload.php';

echo 'getHardcodedControlName(5.1) = '.var_export(
    FrameworkControl::getHardcodedControlName('5.1'), true
).PHP_EOL;

echo 'getHardcodedControlName(5.4.1) = '.var_export(
    FrameworkControl::getHardcodedControlName('5.4.1'), true
).PHP_EOL;

echo 'getHardcodedControlName(8.3) = '.var_export(
    FrameworkControl::getHardcodedControlName('8.3'), true
).PHP_EOL;

echo 'getHardcodedControlName(01.a) = '.var_export(
    FrameworkControl::getHardcodedControlName('01.a'), true
).PHP_EOL;

echo 'getHardcodedControlName(Section 5.1) = '.var_export(
    FrameworkControl::getHardcodedControlName('Section 5.1'), true
).PHP_EOL;
