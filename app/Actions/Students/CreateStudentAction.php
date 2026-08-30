<?php

namespace App\Actions\Students;

use App\Enums\StudentStatus;
use App\Models\Halaqa;
use App\Models\Student;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\StudentTimelineService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateStudentAction
{
    public function __construct(
        private readonly EnrollStudentInHalaqaAction $enrollStudent,
        private readonly AuditLogger $auditLogger,
        private readonly StudentTimelineService $timeline,
    ) {}

    public function execute(array $data, User $actor): Student
    {
        $this->ensureTeacherCanCreateInHalaqa($data, $actor);

        return DB::transaction(function () use ($data, $actor) {
            $names = array_map(fn ($value) => trim((string) $value), [
                $data['first_name'], $data['father_name'], $data['grandfather_name'], $data['family_name'],
            ]);
            $studentData = $data;
            unset($studentData['halaqa_id']);
            $student = Student::query()->create([
                ...$studentData,
                'first_name' => $names[0],
                'father_name' => $names[1],
                'grandfather_name' => $names[2],
                'family_name' => $names[3],
                'full_name' => implode(' ', $names),
                'status' => $data['status'] ?? StudentStatus::Active->value,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
                'current_halaqa_id' => null,
            ]);

            $this->timeline->record($student, 'student.created', 'إنشاء ملف الطالب', $student, occurredAt: $student->registration_date);
            $this->auditLogger->record('student.created', $student, newValues: $student->getAttributes());

            if (! empty($data['halaqa_id'])) {
                $this->enrollStudent->execute(
                    $student,
                    Halaqa::query()->findOrFail($data['halaqa_id']),
                    $data['registration_date'],
                    $actor,
                    'الالتحاق الأول',
                );
            }

            return $student->refresh();
        });
    }

    private function ensureTeacherCanCreateInHalaqa(array $data, User $actor): void
    {
        if (! $actor->requiresTeacherAssignmentScope()) {
            return;
        }

        $halaqaId = (int) ($data['halaqa_id'] ?? 0);
        $teacher = $actor->teacherProfile;
        $assigned = $halaqaId > 0 && $teacher?->active
            && $teacher->assignments()
                ->where('halaqa_id', $halaqaId)
                ->whereDate('starts_at', '<=', today())
                ->where(fn ($dates) => $dates->whereNull('ends_at')->orWhereDate('ends_at', '>=', today()))
                ->whereHas('halaqa', fn ($halaqa) => $halaqa->where('active', true))
                ->exists();

        if (! $assigned) {
            throw ValidationException::withMessages([
                'halaqaId' => 'يمكن للمحفّظ إضافة الطالب إلى حلقة مسندة إليه حاليًا فقط.',
            ]);
        }
    }
}
