<?php

namespace App\Policies;

use App\Models\Halaqa;
use App\Models\User;

class HalaqaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('halaqas.view');
    }

    public function view(User $user, Halaqa $halaqa): bool
    {
        return $user->can('halaqas.view');
    }

    public function create(User $user): bool
    {
        return $user->can('halaqas.manage');
    }

    public function update(User $user, Halaqa $halaqa): bool
    {
        return $user->can('halaqas.manage');
    }

    public function delete(User $user, Halaqa $halaqa): bool
    {
        return $user->can('halaqas.manage');
    }
}
