<?php

namespace App\Http\Resources\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Maturity composite + four dimension scores (1-5 scale) for a radar chart.
 */
class MaturityScoreResource extends JsonResource
{
    public static $wrap = 'data';

    public function toArray(Request $request): array
    {
        return [
            'composite'            => (float) $this['composite'],
            'risk_management'      => (float) $this['risk_management'],
            'control_design'       => (float) $this['control_design'],
            'remediation_velocity' => (float) $this['remediation_velocity'],
            'evidence_audit'       => (float) $this['evidence_audit'],
        ];
    }
}
