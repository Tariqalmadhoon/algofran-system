<?php

namespace App\Services;

use App\Enums\StudentStatus;
use App\Models\Halaqa;
use App\Models\Student;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class MobileTeacherScopeService
{
    public function teacher(User $user): TeacherProfile
    {
        $teacher = $user->teacherProfile()->where('active', true)->first();

        if (! $teacher) {
            throw ValidationException::withMessages([
                'teacher' => 'لا يوجد ملف محفظ فعال لهذا الحساب.',
            ]);
        }

        return $teacher;
    }

    /** @return Builder<Halaqa> */
    public function halaqaQuery(User $user, ?string $date = null): Builder
    {
        $teacher = $this->teacher($user);
        $onDate = $date ?: today()->toDateString();

        return Halaqa::query()
            ->where('active', true)
            ->whereHas('teacherAssignments', fn (Builder $assignments) => $assignments
                ->where('teacher_profile_id', $teacher->id)
                ->whereDate('starts_at', '<=', $onDate)
                ->where(fn (Builder $dates) => $dates
                    ->whereNull('ends_at')
                    ->orWhereDate('ends_at', '>=', $onDate)));
    }

    /** @return Builder<Student> */
    public function studentQuery(User $user, ?string $date = null): Builder
    {
        $onDate = $date ?: today()->toDateString();
        $halaqaIds = $this->halaqaQuery($user, $onDate)->select('halaqas.id');

        return Student::query()
            ->where('status', StudentStatus::Active->value)
            ->whereHas('enrollments', fn (Builder $enrollments) => $enrollments
                ->whereIn('halaqa_id', $halaqaIds)
                ->whereDate('starts_at', '<=', $onDate)
                ->where(fn (Builder $dates) => $dates
                    ->whereNull('ends_at')
                    ->orWhereDate('ends_at', '>=', $onDate)));
    }

    public function canAccessHalaqa(User $user, Halaqa $halaqa, ?string $date = null): bool
    {
        return $this->halaqaQuery($user, $date)->whereKey($halaqa)->exists();
    }

    public function canAccessStudent(User $user, Student $student, ?string $date = null): bool
    {
        return $this->studentQuery($user, $date)->whereKey($student)->exists();
    }
}
