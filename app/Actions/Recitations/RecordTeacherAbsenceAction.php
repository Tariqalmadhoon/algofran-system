<?php

namespace App\Actions\Recitations;

use App\Models\DailyRecord;
use App\Models\Halaqa;
use App\Models\TeacherAbsence;
use App\Models\TeacherProfile;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\TeacherDailyScopeService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordTeacherAbsenceAction
{
    public function __construct(
        private readonly TeacherDailyScopeService $scope,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function execute(
        Halaqa $halaqa,
        TeacherProfile $teacher,
        string $absenceDate,
        string $reason,
        User $actor,
    ): TeacherAbsence {
        return DB::transaction(function () use ($halaqa, $teacher, $absenceDate, $reason, $actor) {
            Halaqa::query()->whereKey($halaqa->id)->lockForUpdate()->firstOrFail();
            $date = $this->scope->ensure($halaqa, $teacher, $absenceDate, $actor);
            $reason = trim($reason);

            if ($reason === '') {
                throw ValidationException::withMessages(['teacherAbsenceReason' => 'سبب غياب المحفّظ مطلوب.']);
            }

            $hasStudentRecords = DailyRecord::query()
                ->where('teacher_profile_id', $teacher->id)
                ->where('halaqa_id', $halaqa->id)
                ->whereDate('record_date', $date)
                ->lockForUpdate()
                ->exists();

            if ($hasStudentRecords) {
                throw ValidationException::withMessages([
                    'teacherAbsenceReason' => 'لا يمكن تسجيل غياب المحفّظ بعد وجود سجلات طلاب في هذا اليوم.',
                ]);
            }

            $absence = TeacherAbsence::query()->updateOrCreate(
                [
                    'teacher_profile_id' => $teacher->id,
                    'halaqa_id' => $halaqa->id,
                    'absence_date' => $date->toDateString(),
                ],
                [
                    'reason' => $reason,
                    'recorded_by' => $actor->id,
                ],
            );

            $this->auditLogger->record(
                $absence->wasRecentlyCreated ? 'teacher.absence.created' : 'teacher.absence.updated',
                $absence,
                newValues: [
                    'teacher_profile_id' => $teacher->id,
                    'halaqa_id' => $halaqa->id,
                    'absence_date' => $date->toDateString(),
                    'reason' => $reason,
                ],
                actor: $actor,
            );

            return $absence->fresh(['halaqa:id,name']);
        }, 3);
    }

    public function remove(TeacherAbsence $absence, User $actor): void
    {
        DB::transaction(function () use ($absence, $actor) {
            $this->scope->ensure(
                $absence->halaqa,
                $absence->teacher,
                $absence->absence_date->toDateString(),
                $actor,
            );
            $oldValues = $absence->only(['teacher_profile_id', 'halaqa_id', 'absence_date', 'reason']);
            $this->auditLogger->record('teacher.absence.removed', $absence, oldValues: $oldValues, actor: $actor);
            $absence->delete();
        }, 3);
    }
}
