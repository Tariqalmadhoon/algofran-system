<?php

namespace App\Actions\Students;

use App\Models\Halaqa;
use App\Models\HalaqaEnrollment;
use App\Models\Student;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\StudentTimelineService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EnrollStudentInHalaqaAction
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly StudentTimelineService $timeline,
    ) {}

    public function execute(Student $student, Halaqa $halaqa, string $startsAt, User $actor, ?string $reason = null): HalaqaEnrollment
    {
        $this->ensureTeacherIsAssignedToHalaqa($halaqa, $actor);

        return DB::transaction(function () use ($student, $halaqa, $startsAt, $actor, $reason) {
            $start = Carbon::parse($startsAt)->startOfDay();
            $student = Student::query()->lockForUpdate()->findOrFail($student->id);
            $current = $student->enrollments()->whereNull('ends_at')->lockForUpdate()->first();

            if (! $halaqa->active) {
                throw ValidationException::withMessages(['halaqa_id' => 'لا يمكن إلحاق الطالب بحلقة متوقفة.']);
            }

            if ($current && $current->halaqa_id === $halaqa->id) {
                throw ValidationException::withMessages(['halaqa_id' => 'الطالب ملتحق بهذه الحلقة بالفعل.']);
            }

            if ($current && $start->lte($current->starts_at)) {
                throw ValidationException::withMessages(['starts_at' => 'تاريخ النقل يجب أن يلي بداية الالتحاق الحالي.']);
            }

            if ($current) {
                $current->update(['ends_at' => $start->copy()->subDay()->toDateString()]);
            }

            $enrollment = $student->enrollments()->create([
                'halaqa_id' => $halaqa->id,
                'starts_at' => $start->toDateString(),
                'reason' => $reason,
                'enrolled_by' => $actor->id,
            ]);
            $student->update(['current_halaqa_id' => $halaqa->id, 'updated_by' => $actor->id]);

            $this->timeline->record(
                $student,
                $current ? 'halaqa.transferred' : 'halaqa.enrolled',
                $current ? 'نقل إلى حلقة جديدة' : 'التحاق بحلقة',
                $enrollment,
                $halaqa->name,
                ['halaqa_id' => $halaqa->id, 'previous_halaqa_id' => $current?->halaqa_id],
                $start,
            );
            $this->auditLogger->record('student.halaqa.enrolled', $enrollment, newValues: $enrollment->getAttributes());

            return $enrollment;
        });
    }

    private function ensureTeacherIsAssignedToHalaqa(Halaqa $halaqa, User $actor): void
    {
        if (! $actor->requiresTeacherAssignmentScope()) {
            return;
        }

        $teacher = $actor->teacherProfile;
        $assigned = $teacher?->active
            && $teacher->assignments()
                ->where('halaqa_id', $halaqa->id)
                ->whereDate('starts_at', '<=', today())
                ->where(fn ($dates) => $dates->whereNull('ends_at')->orWhereDate('ends_at', '>=', today()))
                ->whereHas('halaqa', fn ($assignedHalaqa) => $assignedHalaqa->where('active', true))
                ->exists();

        if (! $assigned) {
            throw ValidationException::withMessages([
                'halaqa_id' => 'يمكن للمحفّظ إلحاق الطالب بحلقة مسندة إليه حاليًا فقط.',
            ]);
        }
    }
}
