<?php

namespace App\Modules\Governance\Services;

use App\Models\ActivityLog;
use App\Modules\Governance\Events\WaiverApproved;
use App\Modules\Governance\Events\WaiverExpired;
use App\Modules\Governance\Models\Policy;
use App\Modules\Governance\Models\PolicyWaiver;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class PolicyWaiverService
{
    public function listForPolicy(Policy $policy): Collection
    {
        return $policy->waivers()
            ->with(['requester', 'approver'])
            ->orderByDesc('created_at')
            ->get();
    }

    public function request(array $data): PolicyWaiver
    {
        $waiver = PolicyWaiver::create([
            'policy_id' => $data['policy_id'],
            'policy_version_id' => $data['policy_version_id'] ?? null,
            'title' => $data['title'],
            'description' => $data['description'],
            'justification' => $data['justification'],
            'requested_by' => Auth::id(),
            'status' => 'pending',
            'effective_date' => $data['effective_date'],
            'expires_at' => $data['expires_at'],
            'department' => $data['department'] ?? null,
            'compensating_controls' => $data['compensating_controls'] ?? null,
        ]);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'waiver_requested',
            'description' => "Waiver requested for policy #{$waiver->policy_id}",
            'details' => [
                'waiver_id' => $waiver->id,
                'policy_id' => $waiver->policy_id,
                'title' => $waiver->title,
            ],
            'ip_address' => request()->ip(),
        ]);

        return $waiver->fresh()->load(['requester', 'policy']);
    }

    public function approve(PolicyWaiver $waiver, int $approverId): PolicyWaiver
    {
        if ($waiver->status !== 'pending') {
            throw new \RuntimeException('Only pending waivers can be approved.');
        }

        $waiver->update([
            'status' => 'approved',
            'approved_by' => $approverId,
        ]);

        WaiverApproved::dispatch($waiver, $waiver->policy, $approverId);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'waiver_approved',
            'description' => "Waiver #{$waiver->id} approved",
            'details' => [
                'waiver_id' => $waiver->id,
                'policy_id' => $waiver->policy_id,
                'approved_by' => $approverId,
            ],
            'ip_address' => request()->ip(),
        ]);

        return $waiver->fresh();
    }

    public function reject(PolicyWaiver $waiver, string $reason): PolicyWaiver
    {
        if ($waiver->status !== 'pending') {
            throw new \RuntimeException('Only pending waivers can be rejected.');
        }

        $waiver->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
        ]);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'waiver_rejected',
            'description' => "Waiver #{$waiver->id} rejected",
            'details' => [
                'waiver_id' => $waiver->id,
                'policy_id' => $waiver->policy_id,
                'reason' => $reason,
            ],
            'ip_address' => request()->ip(),
        ]);

        return $waiver->fresh();
    }

    public function revoke(PolicyWaiver $waiver): PolicyWaiver
    {
        if (!in_array($waiver->status, ['approved', 'pending'])) {
            throw new \RuntimeException('Only approved or pending waivers can be revoked.');
        }

        $waiver->update(['status' => 'revoked']);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'waiver_revoked',
            'description' => "Waiver #{$waiver->id} revoked",
            'details' => [
                'waiver_id' => $waiver->id,
                'policy_id' => $waiver->policy_id,
            ],
            'ip_address' => request()->ip(),
        ]);

        return $waiver->fresh();
    }

    public function expire(PolicyWaiver $waiver): PolicyWaiver
    {
        if ($waiver->status !== 'approved') {
            throw new \RuntimeException('Only approved waivers can expire.');
        }

        $waiver->update(['status' => 'expired']);

        WaiverExpired::dispatch($waiver, $waiver->policy);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'waiver_expired',
            'description' => "Waiver #{$waiver->id} expired",
            'details' => [
                'waiver_id' => $waiver->id,
                'policy_id' => $waiver->policy_id,
            ],
            'ip_address' => request()->ip(),
        ]);

        return $waiver->fresh();
    }

    public function getExpiredWaivers(): Collection
    {
        return PolicyWaiver::with(['policy', 'requester'])
            ->where('status', 'approved')
            ->where('expires_at', '<', now())
            ->get();
    }
}
