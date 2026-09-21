<?php

namespace App\Actions\Students;

use App\Enums\StudentStatus;
use App\Models\Student;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;

class ArchiveStudentAction
{
    public function __construct(
        private readonly UpdateStudentAction $updateStudent,
        private readonly AuditLogger $audit,
    ) {}

    public function execute(Student $student, User $actor): Student
    {
        return DB::transaction(function () use ($student, $actor): Student {
            $student = Student::query()->lockForUpdate()->findOrFail($student->id);
            $old = $student->getAttributes();

            $student->enrollments()
                ->whereNull('ends_at')
                ->whereDate('starts_at', '<=', today())
                ->update(['ends_at' => today()->toDateString()]);

            $archived = $this->updateStudent->execute($student, [
                'status' => StudentStatus::Archived->value,
                'pre_archive_status' => $student->status->value,
                'current_halaqa_id' => null,
            ], $actor);

            $this->audit->record('student.archived', $archived, $old, $archived->getAttributes());

            $archived->delete();

            return $archived;
        });
    }
}
