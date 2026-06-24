<?php

namespace App\Modules\Governance\Services;

use App\Models\ActivityLog;
use App\Modules\Governance\Models\Policy;
use App\Modules\Governance\Models\PolicyException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class PolicyExceptionService
{
    public function listForPolicy(Policy $policy): Collection
    {
        return $policy->exceptions()
            ->with(['requester', 'approver'])
            ->orderByDesc('created_at')
            ->get();
    }

    public function request(array $data): PolicyException
    {
        $exception = PolicyException::create([
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
            'risk_acceptance' => $data['risk_acceptance'] ?? null,
        ]);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'exception_requested',
            'description' => "Exception requested for policy #{$exception->policy_id}",
            'details' => [
                'exception_id' => $exception->id,
                'policy_id' => $exception->policy_id,
                'title' => $exception->title,
            ],
            'ip_address' => request()->ip(),
        ]);

        return $exception->fresh()->load(['requester', 'policy']);
    }

    public function approve(PolicyException $exception, int $approverId): PolicyException
    {
        if ($exception->status !== 'pending') {
            throw new \RuntimeException('Only pending exceptions can be approved.');
        }

        $exception->update([
            'status' => 'approved',
            'approved_by' => $approverId,
        ]);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'exception_approved',
            'description' => "Exception #{$exception->id} approved",
            'details' => [
                'exception_id' => $exception->id,
                'policy_id' => $exception->policy_id,
                'approved_by' => $approverId,
            ],
            'ip_address' => request()->ip(),
        ]);

        return $exception->fresh();
    }

    public function reject(PolicyException $exception, string $reason): PolicyException
    {
        if ($exception->status !== 'pending') {
            throw new \RuntimeException('Only pending exceptions can be rejected.');
        }

        $exception->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
        ]);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'exception_rejected',
            'description' => "Exception #{$exception->id} rejected",
            'details' => [
                'exception_id' => $exception->id,
                'policy_id' => $exception->policy_id,
                'reason' => $reason,
            ],
            'ip_address' => request()->ip(),
        ]);

        return $exception->fresh();
    }

    public function revoke(PolicyException $exception): PolicyException
    {
        if (!in_array($exception->status, ['approved', 'pending'])) {
            throw new \RuntimeException('Only approved or pending exceptions can be revoked.');
        }

        $exception->update(['status' => 'revoked']);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'exception_revoked',
            'description' => "Exception #{$exception->id} revoked",
            'details' => [
                'exception_id' => $exception->id,
                'policy_id' => $exception->policy_id,
            ],
            'ip_address' => request()->ip(),
        ]);

        return $exception->fresh();
    }
}
