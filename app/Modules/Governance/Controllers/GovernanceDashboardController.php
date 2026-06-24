<?php

namespace App\Modules\Governance\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Governance\Services\GovernanceDashboardService;
use Illuminate\Http\Request;

class GovernanceDashboardController extends Controller
{
    public function __construct(
        private GovernanceDashboardService $dashboardService,
    ) {}

    public function index(Request $request)
    {
        $projectId = $request->integer('project_id', null) ?: null;

        $metrics = $this->dashboardService->aggregateMetrics($projectId);
        $domainBreakdown = $this->dashboardService->domainBreakdown();
        $snapshots = $this->dashboardService->getSnapshotHistory();

        return response()->json([
            'metrics' => $metrics,
            'domain_breakdown' => $domainBreakdown,
            'recent_snapshots' => $snapshots,
        ]);
    }

    public function snapshot(Request $request)
    {
        $projectId = $request->integer('project_id', null) ?: null;

        $snapshot = $this->dashboardService->snapshot($projectId);

        return response()->json(['data' => $snapshot, 'message' => 'Snapshot captured.']);
    }
}
