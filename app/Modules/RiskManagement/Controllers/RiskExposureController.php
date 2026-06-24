<?php

namespace App\Modules\RiskManagement\Controllers;

use App\Modules\RiskManagement\Models\RiskRegister;
use App\Modules\RiskManagement\Services\RiskExposureService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RiskExposureController extends Controller
{
    public function __construct(
        private RiskExposureService $service,
    ) {}

    public function show(RiskRegister $risk)
    {
        return response()->json([
            'data' => $this->service->getForRisk($risk->id),
        ]);
    }

    public function calculate(Request $request, RiskRegister $risk)
    {
        $data = $request->validate([
            'financial_amount' => 'nullable|numeric|min:0',
        ]);

        $exposure = $this->service->calculateAndStore(
            $risk,
            $data['financial_amount'] ?? null
        );

        return response()->json(['data' => $exposure], 201);
    }
}
