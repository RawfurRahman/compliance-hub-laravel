<?php

namespace App\Modules\RiskManagement\Services;

use App\Modules\RiskManagement\Models\RiskScenario;
use Illuminate\Support\Facades\Auth;

class RiskScenarioService
{
    public function getForRisk(int $riskId)
    {
        return RiskScenario::where('risk_register_id', $riskId)
            ->orderByDesc('created_at')
            ->get();
    }

    public function create(array $data): RiskScenario
    {
        $data['created_by'] = Auth::id();
        return RiskScenario::create($data);
    }

    public function update(RiskScenario $scenario, array $data): RiskScenario
    {
        $scenario->update($data);
        return $scenario->fresh();
    }

    public function delete(RiskScenario $scenario): void
    {
        $scenario->delete();
    }
}
