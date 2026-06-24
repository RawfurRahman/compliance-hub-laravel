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
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Admin');
    }

    public function update(User $user, Domain $domain): bool
    {
        return $user->hasRole('Admin');
    }

    public function delete(User $user, Domain $domain): bool
    {
        return $user->hasRole('Admin');
    }
}
