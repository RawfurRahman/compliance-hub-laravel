<?php

namespace App\Modules\RiskManagement\Resources\Dashboard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PolicyGovernanceResource extends JsonResource
{
    public static $wrap = 'data';

    public function toArray(Request $request): array
    {
        return [
            'total_policies' => (int) $this['total_policies'],
            'by_status' => [
                'draft' => (int) ($this['by_status']['draft'] ?? 0),
                'under_review' => (int) ($this['by_status']['under_review'] ?? 0),
                'approved' => (int) ($this['by_status']['approved'] ?? 0),
                'published' => (int) ($this['by_status']['published'] ?? 0),
                'deprecated' => (int) ($this['by_status']['deprecated'] ?? 0),
                'archived' => (int) ($this['by_status']['archived'] ?? 0),
                'expired' => (int) ($this['by_status']['expired'] ?? 0),
            ],
            'active_policies' => (int) $this['active_policies'],
            'overdue_reviews' => (int) $this['overdue_reviews'],
            'pending_approvals' => (int) $this['pending_approvals'],
            'domain_breakdown' => $this->when(isset($this['domain_breakdown']), $this['domain_breakdown'], []),
        ];
    }
}
