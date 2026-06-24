<?php

namespace App\Modules\Compliance\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Modules\Compliance\Services\ComplianceSnapshotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ComplianceSnapshotController extends Controller
{
    public function __construct(
        private ComplianceSnapshotService $service,
    ) {}

    public function index(Project $project)
    {
        $snapshots = \App\Modules\Compliance\Models\ComplianceSnapshot::forProject($project->id)
            ->orderBy('snapshot_date', 'desc')
            ->get();
        return view('compliance.snapshots', compact('project', 'snapshots'));
    }

    public function store(Request $request, Project $project): JsonResponse
    {
        $type = $request->get('type', 'ondemand');
        $snapshot = $this->service->takeSnapshot($project->id, $type);
        return response()->json($snapshot, 201);
    }

    public function compare(Project $project, int $from, int $to): JsonResponse
    {
        $result = $this->service->compare($from, $to);
        return response()->json($result);
    }
}
