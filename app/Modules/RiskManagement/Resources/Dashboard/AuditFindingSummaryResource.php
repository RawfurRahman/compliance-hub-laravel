<?php

namespace App\Modules\RiskManagement\Resources\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditFindingSummaryResource extends JsonResource
{
    public static $wrap = 'data';

    public function toArray(Request $request): array
    {
        return [
            'total_findings' => (int) $this['total_findings'],
            'open' => (int) $this['open'],
            'closed' => (int) $this['closed'],
            'overdue' => (int) $this['overdue'],
            'severity_breakdown' => [
                'critical' => (int) ($this['severity_breakdown']['critical'] ?? 0),
                'high' => (int) ($this['severity_breakdown']['high'] ?? 0),
                'medium' => (int) ($this['severity_breakdown']['medium'] ?? 0),
                'low' => (int) ($this['severity_breakdown']['low'] ?? 0),
            ],
            'closure_rate' => (float) $this['closure_rate'],
        ];
    }
}
