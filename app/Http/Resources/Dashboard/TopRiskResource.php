<?php

namespace App\Http\Resources\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A ranked top-risk finding for the leaderboard chart.
 */
class TopRiskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => (int) $this['id'],
            'control'    => $this['control'],
            'title'      => $this['title'],
            'framework'  => $this['framework'],
            'project'    => $this['project'],
            'risk'       => $this['risk'],
            'risk_score' => (int) $this['risk_score'],
        ];
    }
}
