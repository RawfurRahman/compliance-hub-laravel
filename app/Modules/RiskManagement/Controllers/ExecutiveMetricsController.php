<?php

namespace App\Modules\RiskManagement\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\RiskManagement\Services\FinancialExposureService;
use App\Modules\RiskManagement\Services\RemediationMetricsService;
use Illuminate\Http\Request;

/**
 * Dashboard query endpoints for executive metrics: financial exposure and
 * remediation performance (MTTR / SLA). Thin pass-through over the domain
 * services so the dashboard stays a rendering layer.
 */
class ExecutiveMetricsController extends Controller
{
    public function __construct(
        private FinancialExposureService $exposure,
        private RemediationMetricsService $remediation,
    ) {}

    /**
     * Current financial exposure profile (portfolio + category rollups).
     */
    public function financialExposure(Request $request)
    {
        $projectId = $request->filled('project_id') ? (int) $request->get('project_id') : null;

        return response()->json([
            'data' => $this->exposure->forProject($projectId),
        ]);
    }

    /**
     * Current remediation metrics (MTTR / SLA).
     */
    public function remediationMetrics(Request $request)
    {
        $projectId = $request->filled('project_id') ? (int) $request->get('project_id') : null;
        $scope = $request->get('scope', 'all');
        if (!in_array($scope, ['all', 'risk', 'control'], true)) {
            $scope = 'all';
        }

        return response()->json([
            'data' => $this->remediation->forProject($projectId, $scope),
        ]);
    }
}
