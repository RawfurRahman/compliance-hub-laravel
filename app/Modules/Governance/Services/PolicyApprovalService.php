<?php

namespace App\Modules\Governance\Services;

use App\Models\ActivityLog;
use App\Modules\Governance\Events\PolicyApproved;
use App\Modules\Governance\Models\Policy;
use App\Modules\Governance\Models\PolicyApproval;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class PolicyApprovalService
{
    public function listForPolicy(Policy $policy): Collection
    {
        return $policy->approvals()
            ->with(['approver', 'policyVersion'])
            ->orderByDesc('created_at')
            ->get();
    }

    public function requestApproval(Policy $policy, int $approverId, string $type = 'initial'): PolicyApproval
    {
        if (!$policy->isUnderReview()) {
            throw new \RuntimeException('Policy must be under review before requesting approval.');
        }

        $approval = $policy->approvals()->create([
            'policy_version_id' => $policy->current_version > 0
                ? $policy->versions()->where('version_number', $policy->current_version)->first()?->id
                : null,
            'approver_user_id' => $approverId,
            'approval_type' => $type,
            'status' => 'pending',
            'created_by' => 1,
        ]);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'approval_requested',
            'description' => "Approval requested for policy {$policy->policy_number}",
            'details' => [
                'policy_id' => $policy->id,
                'approval_id' => $approval->id,
                'approver_id' => $approverId,
            ],
            'ip_address' => request()->ip(),
        ]);

        return $approval->fresh()->load(['approver']);
    }

    public function approve(PolicyApproval $approval, ?string $comments = null): PolicyApproval
    {
        if ($approval->status !== 'pending') {
            throw new \RuntimeException('Only pending approvals can be approved.');
        }

        $approval->update([
            'status' => 'approved',
            'comments' => $comments,
            'approved_at' => now(),
        ]);

        $policy = $approval->policy;
        $policy->update(['status' => 'approved', 'updated_by' => Auth::id()]);

        PolicyApproved::dispatch($policy, $approval, Auth::id());

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'policy_approved',
            'description' => "Policy {$policy->policy_number} approved via approval #{$approval->id}",
            'details' => [
                'policy_id' => $policy->id,
                'approval_id' => $approval->id,
            ],
            'ip_address' => request()->ip(),
        ]);

        return $approval->fresh();
    }

    public function reject(PolicyApproval $approval, string $reason): PolicyApproval
    {
        if ($approval->status !== 'pending') {
            throw new \RuntimeException('Only pending approvals can be rejected.');
        }

        $approval->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
            'rejected_at' => now(),
        ]);

        $policy = $approval->policy;
        $policy->update(['status' => 'draft', 'updated_by' => Auth::id()]);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'policy_rejected',
            'description' => "Policy {$policy->policy_number} rejected via approval #{$approval->id}",
            'details' => [
                'policy_id' => $policy->id,
                'approval_id' => $approval->id,
                'reason' => $reason,
            ],
            'ip_address' => request()->ip(),
        ]);

        return $approval->fresh();
    }
}
