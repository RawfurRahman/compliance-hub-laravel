<?php

namespace App\Modules\RiskManagement\Resources\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Formats a single persisted residual (after-controls) score record for the
 * dashboard feed.
 *
 * Wraps an App\Modules\RiskManagement\Models\RiskResidualScore. Every field is
 * read straight from the stored record so the feed is reproducible and matches
 * the historical calculation exactly, including manual-override audit info.
 */
class ResidualRiskHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'risk_register_id' => $this->risk_register_id,
            'inherent_score'   => (int) $this->inherent_score,
            'residual_score'   => (int) $this->residual_score,
            'severity_band'    => $this->severity_band,
            'appetite_status'  => $this->appetite_status,
            'reduction_pct'    => (float) $this->reduction_pct,
            'heatmap'          => [
                'likelihood' => (int) $this->heatmap_likelihood,
                'impact'     => (int) $this->heatmap_impact,
            ],
            'trend_direction'  => $this->trend_direction,
            'manual_override'  => (bool) $this->manual_override,
            'override_reason'  => $this->override_reason,
            'formula_version'  => $this->formula_version,
            'source'           => $this->source,
            'explanation'      => $this->explanation,
            'recorded_at'      => optional($this->created_at)->toIso8601String(),
        ];
    }
}
