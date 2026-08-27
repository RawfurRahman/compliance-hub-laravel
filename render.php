<?php

use App\Models\Project;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\MessageBag;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$project = Project::find(7);
$user = User::first();
auth()->login($user);

// mock errors
view()->share('errors', new MessageBag);

$html = app()->call('App\Http\Controllers\EvidenceController@show', ['project' => $project])->render();
file_put_contents('output_evidence_7.html', $html);
echo "HTML saved to output_evidence_7.html\n";
