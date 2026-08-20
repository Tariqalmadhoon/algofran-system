<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Halaqa;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class MobileDataService
{
    public function __construct(private readonly StudentVisibilityService $students) {}

    public function studentQuery(User $user): Builder
    {
        return $this->students->queryFor($user);
    }

    public function studentIds(User $user): array
    {
        return $this->studentQuery($user)->pluck('id')->all();
    }

    public function halaqaQuery(User $user): Builder
    {
        $query = Halaqa::query();
        if ($user->hasAnyRole(['super-admin', 'center-manager', 'academic-supervisor', 'registrar'])) {
            return $query;
        }

        $halaqaIds = $this->studentQuery($user)->whereNotNull('current_halaqa_id')->pluck('current_halaqa_id');

        return $query->whereIn('id', $halaqaIds);
    }

    public function courseQuery(User $user): Builder
    {
        $query = Course::query();
        if ($user->hasAnyRole(['super-admin', 'center-manager', 'academic-supervisor', 'registrar'])) {
            return $query;
        }
        $studentIds = $this->studentIds($user);

        return $query->where(function (Builder $scope) use ($user, $studentIds) {
            $scope->whereHas('enrollments', fn (Builder $enrollments) => $enrollments->whereIn('student_id', $studentIds));
            if ($user->teacherProfile) {
                $scope->orWhere('instructor_id', $user->teacherProfile->id);
            }
        });
    }

    public function perPage(int $requested): int
    {
        return min(max($requested ?: 15, 1), 100);
    }
}
