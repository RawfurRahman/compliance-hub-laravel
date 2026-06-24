<?php

namespace App\Modules\RiskManagement\Resources\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Headline KPI counters. Expects the array from
 * DashboardMetricsService::kpis().
 */
class KpiResource extends JsonResource
{
    public static $wrap = 'data';

    public function toArray(Request $request): array
    {
        return [
            'projects'         => (int) $this['projects'],
            'frameworks'       => (int) $this['frameworks'],
            'total_controls'   => (int) $this['total_controls'],
            'compliant'        => (int) $this['compliant'],
            'open_findings'    => (int) $this['open_findings'],
            'overdue_findings' => (int) $this['overdue_findings'],
            'compliance_pct'   => (float) $this['compliance_pct'],
        ];
    }
}
