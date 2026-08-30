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
        if (! $user->can('students.create')) {
            return false;
        }

        if (! $user->requiresTeacherAssignmentScope()) {
            return true;
        }

        $teacher = $user->teacherProfile;

        return (bool) $teacher?->active
            && $teacher->assignments()
                ->whereDate('starts_at', '<=', today())
                ->where(fn ($dates) => $dates->whereNull('ends_at')->orWhereDate('ends_at', '>=', today()))
                ->whereHas('halaqa', fn ($halaqa) => $halaqa->where('active', true))
                ->exists();
    }

    public function update(User $user, Student $student): bool
    {
        return $user->can('students.update')
            && app(StudentVisibilityService::class)->canView($user, $student);
    }

    public function delete(User $user, Student $student): bool
    {
        return $user->can('students.archive') && ! $user->requiresTeacherAssignmentScope();
    }
}
