<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Project;
use App\Models\EvidenceFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Get admin user
$user = App\Models\User::where('email', 'admin.compliance@gmail.com')->first();
if (!$user) {
    echo "Admin user not found.\n";
    exit(1);
}
Auth::login($user);

$project = Project::first();
if (!$project) {
    echo "Project not found.\n";
    exit(1);
}

// Truncate previous evidence files to start fresh and remove sample data
Schema::disableForeignKeyConstraints();
EvidenceFile::truncate();
DB::table('evidence_feedbacks')->truncate();
DB::table('gap_evidence_links')->truncate();
Schema::enableForeignKeyConstraints();
echo "Truncated evidence tables safely.\n";

$filePath = __DIR__.'/test_evidence.txt';
if (!file_exists($filePath)) {
    file_put_contents($filePath, "This is a test evidence file for checking the unified n8n workflow.");
}

$file = new UploadedFile($filePath, 'test_evidence.txt', 'text/plain', null, true);

$request = Illuminate\Http\Request::create(
    "/evidence/{$project->id}/upload",
    'POST',
    ['requirement_id' => 1],
    [],
    ['file' => $file]
);

$controller = $app->make(\App\Http\Controllers\EvidenceController::class);
$response = $controller->upload($request, $project);
echo "Upload request processed.\n";

// Wait a bit and check database for updates
echo "Waiting 10 seconds for n8n processing...\n";
sleep(10);

$evidence = EvidenceFile::latest()->first();
if ($evidence) {
    echo "Evidence File ID: " . $evidence->id . "\n";
    echo "Scan Status: " . $evidence->scan_status . "\n";
    echo "AI Analysis Status: " . $evidence->ai_analysis_status . "\n";
    echo "AI Observations: " . $evidence->ai_observations . "\n";
    echo "AI Recommendations: " . $evidence->ai_recommendations . "\n";
} else {
    echo "No evidence file created.\n";
}
