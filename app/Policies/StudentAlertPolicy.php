<?php

namespace App\Policies;

use App\Models\StudentAlert;
use App\Models\User;

class StudentAlertPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('alerts.view');
    }

    public function view(User $user, StudentAlert $alert): bool
    {
        return $user->can('alerts.view') && $user->can('view', $alert->student);
    }

    public function update(User $user, StudentAlert $alert): bool
    {
        return $user->can('alerts.manage') && $user->can('view', $alert->student);
    }
}
