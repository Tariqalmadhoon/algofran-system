<?php

namespace App\Actions\Students;

use App\Models\Student;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\StudentTimelineService;

class UpdateStudentAction
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly StudentTimelineService $timeline,
    ) {}

    public function execute(Student $student, array $data, User $actor): Student
    {
        $old = $student->getAttributes();
        $nameFields = ['first_name', 'father_name', 'grandfather_name', 'family_name'];

        if (array_intersect($nameFields, array_keys($data))) {
            $names = array_map(fn (string $field) => trim((string) ($data[$field] ?? $student->{$field})), $nameFields);
            $data = array_merge($data, array_combine($nameFields, $names), ['full_name' => implode(' ', $names)]);
        }

        $student->fill($data + ['updated_by' => $actor->id])->save();
        $this->auditLogger->record('student.updated', $student, $old, $student->getAttributes());

        if (isset($data['status']) && $old['status'] !== $student->getRawOriginal('status')) {
            $this->timeline->record(
                $student,
                'student.status-changed',
                'تغيير حالة الطالب',
                $student,
                "من {$old['status']} إلى {$student->getRawOriginal('status')}",
                ['from' => $old['status'], 'to' => $student->getRawOriginal('status')],
            );
        }

        return $student->refresh();
    }
}
