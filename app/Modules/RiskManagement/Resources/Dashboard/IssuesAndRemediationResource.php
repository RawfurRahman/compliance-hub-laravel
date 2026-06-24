<?php

namespace App\Modules\RiskManagement\Resources\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Issue/remediation status breakdown for a stacked bar chart.
 */
class IssuesAndRemediationResource extends JsonResource
{
    public static $wrap = 'data';

    public function toArray(Request $request): array
    {
        return [
            'open'        => (int) $this['open'],
            'in_progress' => (int) $this['in_progress'],
            'closed'      => (int) $this['closed'],
            'overdue'     => (int) $this['overdue'],
        ];
    }
}
