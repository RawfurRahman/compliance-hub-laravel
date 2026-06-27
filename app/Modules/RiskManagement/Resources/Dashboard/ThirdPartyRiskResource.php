<?php

namespace App\Modules\RiskManagement\Resources\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ThirdPartyRiskResource extends JsonResource
{
    public static $wrap = 'data';

    public function toArray(Request $request): array
    {
        return [
            'total_vendors' => (int) $this['total_vendors'],
            'critical_vendors' => (int) $this['critical_vendors'],
            'active_vendors' => (int) $this['active_vendors'],
            'risk_tier_breakdown' => [
                'critical' => (int) ($this['risk_tier_breakdown']['critical'] ?? 0),
                'high' => (int) ($this['risk_tier_breakdown']['high'] ?? 0),
                'medium' => (int) ($this['risk_tier_breakdown']['medium'] ?? 0),
                'low' => (int) ($this['risk_tier_breakdown']['low'] ?? 0),
            ],
            'assessments' => [
                'total' => (int) ($this['assessments']['total'] ?? 0),
                'completed_recently' => (int) ($this['assessments']['completed_recently'] ?? 0),
                'overdue' => (int) ($this['assessments']['overdue'] ?? 0),
            ],
            'vendor_risk_coverage_pct' => (float) $this['vendor_risk_coverage_pct'],
        ];
    }
}
