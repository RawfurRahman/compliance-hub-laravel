<?php

namespace App\Modules\RiskManagement\Controllers;

use App\Modules\RiskManagement\Models\RiskRegister;
use App\Modules\RiskManagement\Models\RiskTreatmentPlan;
use App\Modules\RiskManagement\Services\RiskTreatmentPlanService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RiskTreatmentPlanController extends Controller
{
    public function __construct(
        private RiskTreatmentPlanService $service,
    ) {}

    public function index(RiskRegister $risk)
    {
        return response()->json([
            'data' => $this->service->getForRisk($risk->id),
        ]);
    }

    public function store(Request $request, RiskRegister $risk)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'treatment_type' => 'required|in:avoid,reduce,transfer,accept',
            'description' => 'nullable|string',
            'controls_required' => 'nullable|string',
            'responsible_party' => 'nullable|string|max:255',
            'budget_estimated' => 'nullable|numeric',
            'budget_actual' => 'nullable|numeric',
            'start_date' => 'nullable|date',
            'target_date' => 'nullable|date',
            'status' => 'sometimes|in:planned,in_progress,completed,cancelled',
            'progress_pct' => 'sometimes|integer|min:0|max:100',
            'notes' => 'nullable|string',
        ]);

        $data['risk_register_id'] = $risk->id;
        $plan = $this->service->create($data);

        return response()->json(['data' => $plan], 201);
    }

    public function update(Request $request, RiskRegister $risk, RiskTreatmentPlan $plan)
    {
        $data = $request->validate([
            'title' => 'sometimes|string|max:255',
            'treatment_type' => 'sometimes|in:avoid,reduce,transfer,accept',
            'description' => 'nullable|string',
            'controls_required' => 'nullable|string',
            'responsible_party' => 'nullable|string|max:255',
            'budget_estimated' => 'nullable|numeric',
            'budget_actual' => 'nullable|numeric',
            'start_date' => 'nullable|date',
            'target_date' => 'nullable|date',
            'status' => 'sometimes|in:planned,in_progress,completed,cancelled',
            'progress_pct' => 'sometimes|integer|min:0|max:100',
            'effectiveness_rating' => 'nullable|integer|min:0|max:100',
            'notes' => 'nullable|string',
        ]);

        $plan = $this->service->update($plan, $data);

        return response()->json(['data' => $plan]);
    }

    public function destroy(RiskRegister $risk, RiskTreatmentPlan $plan)
    {
        $this->service->delete($plan);
        return response()->json(['success' => true]);
    }
}
