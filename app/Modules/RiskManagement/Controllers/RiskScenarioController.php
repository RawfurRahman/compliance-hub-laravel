<?php

namespace App\Modules\RiskManagement\Controllers;

use App\Modules\RiskManagement\Models\RiskRegister;
use App\Modules\RiskManagement\Models\RiskScenario;
use App\Modules\RiskManagement\Services\RiskScenarioService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RiskScenarioController extends Controller
{
    public function __construct(
        private RiskScenarioService $service,
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
            'description' => 'nullable|string',
            'threat_source' => 'nullable|string|max:100',
            'threat_event' => 'nullable|string|max:255',
            'vulnerability_factor' => 'nullable|string',
            'scenario_date' => 'nullable|date',
        ]);

        $data['risk_register_id'] = $risk->id;
        $scenario = $this->service->create($data);

        return response()->json(['data' => $scenario], 201);
    }

    public function update(Request $request, RiskRegister $risk, RiskScenario $scenario)
    {
        $data = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'threat_source' => 'nullable|string|max:100',
            'threat_event' => 'nullable|string|max:255',
            'vulnerability_factor' => 'nullable|string',
            'scenario_date' => 'nullable|date',
        ]);

        $scenario = $this->service->update($scenario, $data);

        return response()->json(['data' => $scenario]);
    }

    public function destroy(RiskRegister $risk, RiskScenario $scenario)
    {
        $this->service->delete($scenario);
        return response()->json(['success' => true]);
    }
}
