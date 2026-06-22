<?php

try {
    require 'vendor/autoload.php';
    $app = require_once 'bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    
    // Get the artisan application using Reflection
    $refKernel = new ReflectionClass($kernel);
    $method = $refKernel->getMethod('getArtisan');
    $method->setAccessible(true);
    $artisan = $method->invoke($kernel);
    
    if ($artisan) {
        $refArtisan = new ReflectionClass($artisan);
        $prop = $refArtisan->getProperty('laravel');
        $prop->setAccessible(true);
        $val = $prop->getValue($artisan);
        echo "Artisan app class: " . get_class($artisan) . "\n";
        echo "Artisan app container: " . ($val ? "NOT NULL" : "NULL") . "\n";
    } else {
        echo "Artisan application is null!\n";
    }
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
