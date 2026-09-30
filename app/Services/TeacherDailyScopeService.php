<?php

namespace App\Services;

use App\Models\Halaqa;
use App\Models\TeacherProfile;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class TeacherDailyScopeService
{
    public function ensure(
        Halaqa $halaqa,
        TeacherProfile $teacher,
        string $recordDate,
        User $actor,
    ): Carbon {
        $date = Carbon::parse($recordDate)->startOfDay();

        if ($date->isFuture()) {
            throw ValidationException::withMessages([
                'record_date' => 'لا يمكن التسجيل بتاريخ مستقبلي.',
            ]);
        }

        $actorTeacherProfile = $actor->teacherProfile;
        if ($actorTeacherProfile && (int) $actorTeacherProfile->id !== (int) $teacher->id) {
            throw ValidationException::withMessages([
                'teacher' => 'لا يمكنك التسجيل باسم محفّظ آخر.',
            ]);
        }

        if (! $actor->active || $actor->archived_at || ! $teacher->active || ! $teacher->user?->active || ! $halaqa->active) {
            throw ValidationException::withMessages([
                'halaqa_id' => 'الحساب التعليمي أو الحلقة غير فعّال حاليًا.',
            ]);
        }

        $assigned = $halaqa->teacherAssignments()
            ->where('teacher_profile_id', $teacher->id)
            ->whereDate('starts_at', '<=', $date)
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhereDate('ends_at', '>=', $date))
            ->exists();

        if (! $assigned) {
            throw ValidationException::withMessages([
                'halaqa_id' => 'المحفّظ غير مسند إلى هذه الحلقة في التاريخ المحدد.',
            ]);
        }

        return $date;
    }
}
