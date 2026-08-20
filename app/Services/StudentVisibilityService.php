<?php

namespace App\Services;

use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class StudentVisibilityService
{
    /** @return Builder<Student> */
    public function queryFor(User $user): Builder
    {
        $query = Student::query();

        if ($user->hasAnyRole(['super-admin', 'center-manager', 'academic-supervisor', 'registrar'])) {
            return $query;
        }

        if ($user->hasRole('teacher')) {
            return $query->whereHas('currentHalaqa.teacherAssignments', function (Builder $assignment) use ($user) {
                $assignment->whereHas('teacher', fn (Builder $teacher) => $teacher->where('user_id', $user->id))
                    ->whereDate('starts_at', '<=', today())
                    ->where(fn (Builder $dates) => $dates->whereNull('ends_at')->orWhereDate('ends_at', '>=', today()));
            });
        }

        if ($user->hasRole('guardian')) {
            return $query->whereHas('guardians', fn (Builder $guardian) => $guardian->where('user_id', $user->id));
        }

        if ($user->hasRole('student')) {
            return $query->where('user_id', $user->id);
        }

        return $user->can('students.view') ? $query : $query->whereRaw('1 = 0');
    }

    public function canView(User $user, Student $student): bool
    {
        return $this->queryFor($user)->whereKey($student)->exists();
    }
}
