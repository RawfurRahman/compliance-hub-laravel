<?php

namespace App\Modules\Governance\Policies;

use App\Models\User;
use App\Modules\Governance\Models\Domain;
use Illuminate\Auth\Access\HandlesAuthorization;

class DomainAccessPolicy
{
    use HandlesAuthorization;

    public function view(User $user, Domain $domain): bool
    {
        if ($user->hasRole('Super Admin') || $user->hasRole('Admin') || $user->hasRole('Auditor')) {
            return true;
        }

        return $domain->ownershipMatrix()
            ->where('user_id', $user->id)
            ->where('domain_id', $domain->id)
            ->whereIn('role', ['owner', 'admin', 'reviewer', 'approver'])
            ->exists()
            || $domain->owner_user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Super Admin') || $user->hasRole('Admin') || $user->hasRole('Auditor');
    }

    public function update(User $user, Domain $domain): bool
    {
        if ($user->hasRole('Super Admin') || $user->hasRole('Admin') || $user->hasRole('Auditor')) {
            return true;
        }

        return $domain->owner_user_id === $user->id;
    }

    public function delete(User $user, Domain $domain): bool
    {
        return $user->hasRole('Super Admin') || $user->hasRole('Admin') || $user->hasRole('Auditor');
    }
}
