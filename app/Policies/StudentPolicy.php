<?php

namespace App\Policies;

use App\Models\Student;
use App\Models\User;
use App\Services\StudentVisibilityService;

class StudentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('students.view');
    }

    public function view(User $user, Student $student): bool
    {
        return $user->can('students.view') && app(StudentVisibilityService::class)->canView($user, $student);
    }

    public function create(User $user): bool
    {
        return $user->can('students.create');
    }

    public function update(User $user, Student $student): bool
    {
        return $user->can('students.update') && ! $user->hasRole('teacher');
    }

    public function delete(User $user, Student $student): bool
    {
        return $user->can('students.archive') && ! $user->hasRole('teacher');
    }
}
