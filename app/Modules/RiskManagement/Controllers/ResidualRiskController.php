<?php

namespace App\Modules\RiskManagement\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\RiskManagement\Models\RiskRegister;
use App\Modules\RiskManagement\Resources\Dashboard\ResidualRiskHistoryResource;
use App\Modules\RiskManagement\Services\ResidualRiskService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Endpoints surfacing the residual (after-controls) scoring engine: history,
 * inherent-vs-residual trend series, and manual override.
 */
class ResidualRiskController extends Controller
{
    public function __construct(
        private ResidualRiskService $service,
    ) {}

    /**
     * Chronological residual score history for a single risk.
     */
    public function history(RiskRegister $risk)
    {
        return ResidualRiskHistoryResource::collection(
            $this->service->historyForRisk($risk->id)
        );
    }

    /**
     * Inherent-vs-residual trend series for a single risk.
     */
    public function trend(RiskRegister $risk)
    {
        return response()->json([
            'data' => $this->service->trendSeries($risk->id),
        ]);
    }

    /**
     * Apply a manual override of the residual score, with audit logging.
     */
    public function override(Request $request, RiskRegister $risk)
    {
        $data = $request->validate([
            'residual_score' => 'required|integer|min:0',
            'reason'         => 'required|string|max:500',
        ]);

        $result = $this->service->overrideScore(
            $this->service->buildInputFromRisk($risk),
            (int) $data['residual_score'],
            $data['reason'],
            risk: $risk,
            recordedBy: Auth::id(),
        );

        return response()->json(['data' => $result->toArray()], 201);
    }
}
