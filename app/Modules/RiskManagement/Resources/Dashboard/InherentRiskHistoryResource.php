<?php

namespace App\Modules\RiskManagement\Resources\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Formats a single persisted inherent (before-controls) score record for the
 * dashboard feed.
 *
 * Wraps an App\Modules\RiskManagement\Models\RiskInherentScore. Every field is
 * read straight from the stored record so the feed is reproducible and matches
 * the historical calculation exactly.
 */
class InherentRiskHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'risk_register_id' => $this->risk_register_id,
            'tv_score'         => (int) $this->tv_score,
            'inherent_score'   => (int) $this->inherent_score,
            'severity_band'    => $this->severity_band,
            'appetite_status'  => $this->appetite_status,
            'heatmap'          => [
                'likelihood' => (int) $this->heatmap_likelihood,
                'impact'     => (int) $this->heatmap_impact,
            ],
            'risk_ranking'     => (float) $this->risk_ranking,
            'formula_version'  => $this->formula_version,
            'source'           => $this->source,
            'explanation'      => $this->explanation,
            'recorded_at'      => optional($this->created_at)->toIso8601String(),
        ];
    }
}
