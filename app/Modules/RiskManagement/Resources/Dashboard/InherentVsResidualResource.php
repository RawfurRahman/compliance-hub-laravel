<?php

namespace App\Modules\RiskManagement\Resources\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Inherent vs residual weighted risk for one domain ("department").
 */
class InherentVsResidualResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'department' => $this['department'],
            'inherent'   => (int) $this['inherent'],
            'residual'   => (int) $this['residual'],
        ];
    }
}
