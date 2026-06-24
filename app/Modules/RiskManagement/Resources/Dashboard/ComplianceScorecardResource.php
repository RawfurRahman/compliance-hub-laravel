<?php

namespace App\Modules\RiskManagement\Resources\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Per-framework compliance scorecard row.
 *
 * Exposes BOTH the raw percentage and the lifecycle phase, plus an explicit
 * fully_compliant flag. The flag is governed by the phase (only final_done is
 * fully compliant), never by the percentage alone, so a framework stuck in
 * the Gap phase cannot be shown as fully compliant.
 */
class ComplianceScorecardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'framework'       => $this['framework'],
            'percentage'      => (float) $this['percentage'],
            'phase'           => $this['phase'],
            'fully_compliant' => (bool) $this['fully_compliant'],
        ];
    }
}
