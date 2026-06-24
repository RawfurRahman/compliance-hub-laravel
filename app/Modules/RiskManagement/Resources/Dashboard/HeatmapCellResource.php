<?php

namespace App\Modules\RiskManagement\Resources\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A single risk heatmap cell: likelihood x impact -> count.
 */
class HeatmapCellResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'likelihood' => $this['likelihood'],
            'impact'     => $this['impact'],
            'count'      => (int) $this['count'],
        ];
    }
}
