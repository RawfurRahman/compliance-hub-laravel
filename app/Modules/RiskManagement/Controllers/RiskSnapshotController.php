<?php

namespace App\Modules\RiskManagement\Controllers;

use App\Modules\RiskManagement\Models\Project;
use App\Modules\RiskManagement\Services\RiskSnapshotService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RiskSnapshotController extends Controller
{
    public function __construct(
        private RiskSnapshotService $service,
    ) {}

    public function index(Request $request)
    {
        $projectId = $request->get('project_id');
        $type = $request->get('type', 'full');

        $snapshot = $this->service->latestByProject((int) $projectId, $type);

        return response()->json(['data' => $snapshot]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'project_id' => 'required|integer|exists:projects,id',
            'type' => 'sometimes|in:full,exposure,heatmap',
        ]);

        $snapshot = $this->service->takeSnapshot(
            $data['project_id'],
            $data['type'] ?? 'full'
        );

        return response()->json(['data' => $snapshot], 201);
    }
}
