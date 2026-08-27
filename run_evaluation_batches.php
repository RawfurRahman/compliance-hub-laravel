<?php
/**
 * Batch evaluation runner — processes corpus items in small batches
 * with Ollama restarts between batches to avoid memory pressure.
 */
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\EvaluationCorpusItem;

$total = EvaluationCorpusItem::count();
$batchSize = 4;
$batches = ceil($total / $batchSize);

echo "Total corpus items: {$total}\n";
echo "Batch size: {$batchSize}\n";
echo "Batches needed: {$batches}\n\n";

for ($batch = 0; $batch < $batches; $batch++) {
    $offset = $batch * $batchSize;
    $remaining = min($batchSize, $total - $offset);
    
    echo "=== Batch " . ($batch + 1) . "/{$batches} (items " . ($offset + 1) . "-" . ($offset + $remaining) . ") ===\n";
    
    // Restart Ollama between batches to free memory
    if ($batch > 0) {
        echo "Restarting Ollama to free memory...\n";
        exec('ollama stop llava:7b 2>&1', $out, $code);
        sleep(5);
        
        // Verify Ollama is responsive
        $ch = curl_init('http://localhost:11434/api/tags');
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
        
        if ($response === false) {
            echo "ERROR: Ollama not responding after restart. Waiting 10s...\n";
            sleep(10);
        } else {
            echo "Ollama is responsive.\n";
        }
    }
    
    // Run the artisan command
    $cmd = sprintf(
        'php artisan evaluation:run-corpus --sync --offset=%d --limit=%d 2>&1',
        $offset,
        $remaining
    );
    
    echo "Running: {$cmd}\n";
    $output = shell_exec($cmd);
    echo $output . "\n";
    
    // Check results
    $runItems = \App\Models\EvaluationRunItem::count();
    echo "Total evaluation_run_items so far: {$runItems}\n\n";
}

echo "=== ALL BATCHES COMPLETE ===\n";
echo "Total evaluation_run_items: " . \App\Models\EvaluationRunItem::count() . "\n";
