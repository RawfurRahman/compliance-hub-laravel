<?php

namespace App\Modules\RiskManagement\Resources\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OwnershipMatrixResource extends JsonResource
{
    public static $wrap = 'data';

    public function toArray(Request $request): array
    {
        return [
            'total_assignments' => (int) $this['total_assignments'],
            'primary_owners' => (int) $this['primary_owners'],
            'by_role' => [
                'owner' => (int) ($this['by_role']['owner'] ?? 0),
                'reviewer' => (int) ($this['by_role']['reviewer'] ?? 0),
                'approver' => (int) ($this['by_role']['approver'] ?? 0),
                'stakeholder' => (int) ($this['by_role']['stakeholder'] ?? 0),
            ],
            'by_business_unit' => $this->when(isset($this['by_business_unit']), $this['by_business_unit'], []),
            'coverage_pct' => (float) $this['coverage_pct'],
            'gaps' => (int) $this['gaps'],
        ];
    }
}
