<?php

namespace App\Actions\Students;

use App\Enums\StudentStatus;
use App\Models\Student;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\StudentTimelineService;
use Illuminate\Support\Facades\DB;

class RestoreStudentAction
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly StudentTimelineService $timeline,
    ) {}

    public function execute(Student $student, User $actor): Student
    {
        return DB::transaction(function () use ($student, $actor): Student {
            $student = Student::withTrashed()->lockForUpdate()->findOrFail($student->id);
            $old = $student->getAttributes();
            $restoredStatus = StudentStatus::tryFrom((string) $student->pre_archive_status);
            $restoredStatus = $restoredStatus && $restoredStatus !== StudentStatus::Archived
                ? $restoredStatus
                : StudentStatus::Active;

            $student->restore();
            $student->forceFill([
                'status' => $restoredStatus->value,
                'pre_archive_status' => null,
                'current_halaqa_id' => null,
                'updated_by' => $actor->id,
            ])->save();

            $this->timeline->record(
                $student,
                'student.restored',
                'استعادة ملف الطالب من سلة المهملات',
                $student,
                'أعيد الملف إلى قائمة الطلاب ويحتاج إلى إلحاق جديد بحلقة عند الحاجة.',
                ['restored_status' => $restoredStatus->value],
            );
            $this->audit->record('student.restored', $student, $old, $student->getAttributes(), $actor);

            return $student->refresh();
        });
    }
}
