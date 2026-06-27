<?php

namespace App\Modules\RiskManagement\Resources\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FinancialExposureTrendResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this['id'],
            'date' => $this['date'],
            'sle' => $this['sle'],
            'ale' => $this['ale'],
            'portfolio_exposure' => $this['portfolio_exposure'],
            'risk_count' => $this['risk_count'],
            'remediation_cost' => $this['remediation_cost'],
        ];
    }
}
