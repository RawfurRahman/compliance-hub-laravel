<?php

namespace App\Modules\Governance\Services;

use App\Models\ActivityLog;
use App\Modules\Governance\Models\OwnershipMatrix;
use App\Modules\Governance\Models\Policy;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class OwnershipService
{
    public function getMatrixForPolicy(Policy $policy): Collection
    {
        return $policy->ownershipMatrix()->with('user')->orderBy('role')->get();
    }

    public function assignOwner(Policy $policy, array $data): OwnershipMatrix
    {
        $assignment = $policy->ownershipMatrix()->create([
            'user_id' => $data['user_id'] ?? null,
            'role' => $data['role'],
            'department' => $data['department'] ?? null,
            'business_unit' => $data['business_unit'] ?? null,
            'is_primary' => $data['is_primary'] ?? false,
            'assigned_at' => now(),
        ]);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'ownership_assigned',
            'description' => "Ownership assigned for policy {$policy->policy_number}: role={$data['role']}",
            'details' => [
                'policy_id' => $policy->id,
                'assignment_id' => $assignment->id,
                'role' => $data['role'],
                'user_id' => $data['user_id'] ?? null,
            ],
            'ip_address' => request()->ip(),
        ]);

        return $assignment->fresh()->load('user');
    }

    public function removeAssignment(int $id): void
    {
        $assignment = OwnershipMatrix::findOrFail($id);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'ownership_removed',
            'description' => "Ownership removed from policy #{$assignment->policy_id}",
            'details' => [
                'policy_id' => $assignment->policy_id,
                'assignment_id' => $assignment->id,
                'role' => $assignment->role,
            ],
            'ip_address' => request()->ip(),
        ]);

        $assignment->delete();
    }
}
