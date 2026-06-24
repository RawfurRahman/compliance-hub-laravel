<?php

namespace App\Modules\RiskManagement\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\RiskManagement\Models\RiskRegister;
use App\Modules\RiskManagement\Resources\Dashboard\InherentRiskHistoryResource;
use App\Modules\RiskManagement\Services\RiskScoringService;
use Illuminate\Http\Request;

/**
 * Read-only endpoints surfacing the inherent (before-controls) scoring engine
 * history and the before-vs-after-controls comparison feed for dashboards.
 */
class RiskInherentScoreController extends Controller
{
    public function __construct(
        private RiskScoringService $service,
    ) {}

    /**
     * Chronological inherent score history for a single risk.
     */
    public function history(RiskRegister $risk)
    {
        return InherentRiskHistoryResource::collection(
            $this->service->historyForRisk($risk->id)
        );
    }

    /**
     * Before-vs-after-controls comparison feed for a project.
     */
    public function beforeAfter(Request $request)
    {
        $data = $request->validate([
            'project_id' => 'required|integer|exists:projects,id',
        ]);

        return response()->json([
            'data' => $this->service->beforeAfterControls((int) $data['project_id']),
        ]);
    }
}
