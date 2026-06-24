<?php

namespace App\Modules\RiskManagement\Resources\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Effective / partial / ineffective control counts for a donut chart.
 */
class ControlEffectivenessResource extends JsonResource
{
    public static $wrap = 'data';

    public function toArray(Request $request): array
    {
        return [
            'effective'   => (int) $this['effective'],
            'partial'     => (int) $this['partial'],
            'ineffective' => (int) $this['ineffective'],
        ];
    }
}
