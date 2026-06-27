<?php

namespace App\Modules\Governance\Policies;

use App\Models\User;
use App\Modules\Governance\Models\Policy;
use Illuminate\Auth\Access\HandlesAuthorization;

class PolicyAccessPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Policy $policy): bool
    {
        if ($user->hasRole('Super Admin') || $user->hasRole('Admin') || $user->hasRole('Auditor')) {
            return true;
        }

        return $policy->owner_user_id === $user->id
            || $policy->created_by === $user->id
            || $policy->ownershipMatrix()
                ->where('user_id', $user->id)
                ->whereIn('role', ['reviewer', 'approver', 'stakeholder'])
                ->exists();
    }

    public function create(User $user): bool
    {
        if ($user->hasRole('Super Admin') || $user->hasRole('Admin') || $user->hasRole('Auditor')) {
            return true;
        }

        return $user->hasRole('policy_owner');
    }

    public function update(User $user, Policy $policy): bool
    {
        if ($user->hasRole('Admin')) {
            return true;
        }

        return $policy->owner_user_id === $user->id
            || $policy->created_by === $user->id;
    }

    public function delete(User $user, Policy $policy): bool
    {
        return $user->hasRole('Admin') || $policy->owner_user_id === $user->id;
    }

    public function review(User $user, Policy $policy): bool
    {
        if ($user->hasRole('Admin')) {
            return true;
        }

        return $policy->ownershipMatrix()
            ->where('user_id', $user->id)
            ->whereIn('role', ['reviewer', 'policy_owner'])
            ->exists();
    }

    public function approve(User $user, Policy $policy): bool
    {
        if ($user->hasRole('Admin')) {
            return true;
        }

        return $policy->ownershipMatrix()
            ->where('user_id', $user->id)
            ->whereIn('role', ['approver', 'policy_owner'])
            ->exists();
    }

    public function publish(User $user, Policy $policy): bool
    {
        return $user->hasRole('Admin') || $policy->owner_user_id === $user->id;
    }

    public function waive(User $user): bool
    {
        return $user->hasRole('Admin');
    }

    public function expire(User $user, Policy $policy): bool
    {
        return $user->hasRole('Admin') || $policy->owner_user_id === $user->id;
    }
}
