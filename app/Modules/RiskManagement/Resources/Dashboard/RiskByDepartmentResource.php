<?php

namespace App\Modules\RiskManagement\Resources\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Open finding count and weighted risk score for one domain ("department").
 */
class RiskByDepartmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'department' => $this['department'],
            'open_count' => (int) $this['open_count'],
            'risk_score' => (int) $this['risk_score'],
        ];
    }
}
