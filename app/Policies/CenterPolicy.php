<?php

namespace App\Policies;

use App\Models\Center;
use App\Models\User;

class CenterPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('organization.view');
    }

    public function view(User $user, Center $center): bool
    {
        return $user->can('organization.view');
    }

    public function create(User $user): bool
    {
        return $user->can('organization.manage');
    }

    public function update(User $user, Center $center): bool
    {
        return $user->can('organization.manage');
    }

    public function delete(User $user, Center $center): bool
    {
        return $user->can('organization.manage');
    }
}
