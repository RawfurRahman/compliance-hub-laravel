<?php

namespace App\Modules\RiskManagement\Resources\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RemediationTrendResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'month' => $this['month'],
            'opened' => $this['opened'],
            'closed' => $this['closed'],
            'overdue' => $this['overdue'],
            'mttr_hours' => $this['mttr_hours'],
            'sla_breach_rate' => $this['sla_breach_rate'],
            'closure_rate' => $this['closure_rate'],
            'aging_buckets' => $this['aging_buckets'],
        ];
    }
}
