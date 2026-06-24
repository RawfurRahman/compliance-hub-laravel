<?php

namespace App\Modules\RiskManagement\Resources\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Accepted / mitigated / open risk-treatment split for a pie chart.
 */
class RiskAcceptanceSplitResource extends JsonResource
{
    public static $wrap = 'data';

    public function toArray(Request $request): array
    {
        return [
            'accepted'  => (int) $this['accepted'],
            'mitigated' => (int) $this['mitigated'],
            'open'      => (int) $this['open'],
        ];
    }
}
