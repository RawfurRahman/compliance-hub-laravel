<?php

namespace App\Modules\RiskManagement\Resources\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IssuesRemediationTrendResource extends JsonResource
{
    public static $wrap = 'data';

    public function toArray(Request $request): array
    {
        return [
            'month' => (string) $this['month'],
            'critical' => (int) ($this['critical'] ?? 0),
            'high' => (int) ($this['high'] ?? 0),
            'medium' => (int) ($this['medium'] ?? 0),
            'low' => (int) ($this['low'] ?? 0),
            'total' => (int) ($this['total'] ?? 0),
        ];
    }
}
