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

        if ($user->hasRole('super-admin')) {
            return $query;
        }

        if ($user->hasAnyRole(['center-manager', 'academic-supervisor', 'registrar'])) {
            $centerId = $user->staffProfile?->center_id;

            return $centerId
                ? $query->whereHas('currentHalaqa', fn (Builder $halaqa) => $halaqa->where('center_id', $centerId))
                : $query->whereRaw('1 = 0');
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

        return $query->whereRaw('1 = 0');
    }

    public function canView(User $user, Student $student): bool
    {
        return $this->queryFor($user)->whereKey($student)->exists();
    }

    /** @return Builder<Student> */
    public function trashedQueryFor(User $user): Builder
    {
        $query = Student::onlyTrashed();

        if ($user->hasRole('super-admin')) {
            return $query;
        }

        if ($user->hasAnyRole(['center-manager', 'academic-supervisor', 'registrar'])) {
            $centerId = $user->staffProfile?->center_id;

            return $centerId
                ? $query->whereHas('enrollments.halaqa', fn (Builder $halaqa) => $halaqa->where('center_id', $centerId))
                : $query->whereRaw('1 = 0');
        }

        return $query->whereRaw('1 = 0');
    }
}
