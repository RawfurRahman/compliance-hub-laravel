<?php

namespace App\Modules\RiskManagement\Resources\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IssueAgingResource extends JsonResource
{
    public static $wrap = 'data';

    public function toArray(Request $request): array
    {
        return [
            'buckets' => [
                '0-7' => (int) ($this['buckets']['0-7'] ?? 0),
                '8-30' => (int) ($this['buckets']['8-30'] ?? 0),
                '31-60' => (int) ($this['buckets']['31-60'] ?? 0),
                '61-90' => (int) ($this['buckets']['61-90'] ?? 0),
                '90+' => (int) ($this['buckets']['90+'] ?? 0),
            ],
            'total_aging' => (int) $this['total_aging'],
            'source_breakdown' => [
                'assessment_findings' => (array) ($this['source_breakdown']['assessment_findings'] ?? []),
                'audit_findings' => (array) ($this['source_breakdown']['audit_findings'] ?? []),
                'treatment_plans' => (array) ($this['source_breakdown']['treatment_plans'] ?? []),
            ],
        ];
    }
}
