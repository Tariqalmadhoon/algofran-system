<?php

namespace App\Policies;

use App\Models\Certificate;
use App\Models\User;

class CertificatePolicy
{
    public function view(User $user, Certificate $certificate): bool
    {
        return $user->can('certificates.manage') || $user->can('view', $certificate->student);
    }

    public function create(User $user): bool
    {
        return $user->can('certificates.manage');
    }
}
