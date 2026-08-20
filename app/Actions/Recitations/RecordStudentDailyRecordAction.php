<?php

namespace App\Actions\Recitations;

use App\Enums\AttendanceStatus;
use App\Enums\EvaluationRating;
use App\Enums\RecitationType;
use App\Models\Attendance;
use App\Models\DailyRecord;
use App\Models\Halaqa;
use App\Models\Student;
use App\Models\TeacherProfile;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\QuranRangeService;
use App\Services\StudentAchievementEngine;
use App\Services\StudentAlertEngine;
use App\Services\StudentProgressService;
use App\Services\StudentTimelineService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordStudentDailyRecordAction
{
    public function __construct(
        private readonly QuranRangeService $quranRange,
        private readonly StudentTimelineService $timeline,
        private readonly AuditLogger $auditLogger,
        private readonly StudentProgressService $progress,
        private readonly StudentAlertEngine $alerts,
        private readonly StudentAchievementEngine $achievements,
    ) {}

    public function execute(Student $student, Halaqa $halaqa, TeacherProfile $teacher, array $data, User $actor): DailyRecord
    {
        return DB::transaction(function () use ($student, $halaqa, $teacher, $data, $actor) {
            $date = Carbon::parse($data['record_date'])->startOfDay();
            $this->ensureActorCanRecord($actor, $teacher, $halaqa, $date);
            $this->ensureStudentWasEnrolled($student, $halaqa, $date);

            if ($student->dailyRecords()->whereDate('record_date', $date)->exists()) {
                throw ValidationException::withMessages(['record_date' => 'يوجد سجل يومي لهذا الطالب في التاريخ المحدد.']);
            }

            $attendanceStatus = AttendanceStatus::from($data['attendance_status']);
            $generalEvaluation = empty($data['general_evaluation'])
                ? null
                : EvaluationRating::from($data['general_evaluation'])->value;
            $items = array_values(array_filter($data['items'] ?? [], fn (array $item) => ! empty($item['enabled'])));

            if ($attendanceStatus === AttendanceStatus::Absent && count($items) > 0) {
                throw ValidationException::withMessages(['items' => 'لا يمكن تسجيل تسميع لطالب غائب.']);
            }

            $validatedItems = [];
            foreach ($items as $item) {
                [$start, $end] = $this->quranRange->validate((int) $item['start_ayah_id'], (int) $item['end_ayah_id']);
                $validatedItems[] = [
                    'type' => RecitationType::from($item['type'])->value,
                    'start_ayah_id' => $start->id,
                    'end_ayah_id' => $end->id,
                    'evaluation' => EvaluationRating::from($item['evaluation'])->value,
                    'notes' => $item['notes'] ?? null,
                    'memorization_errors' => max(0, (int) ($item['memorization_errors'] ?? 0)),
                    'tajweed_errors' => max(0, (int) ($item['tajweed_errors'] ?? 0)),
                    'hesitation_count' => max(0, (int) ($item['hesitation_count'] ?? 0)),
                    'teacher_prompt_count' => max(0, (int) ($item['teacher_prompt_count'] ?? 0)),
                ];
            }

            $attendance = Attendance::query()->create([
                'student_id' => $student->id,
                'halaqa_id' => $halaqa->id,
                'record_date' => $date->toDateString(),
                'status' => $attendanceStatus->value,
                'recorded_by' => $actor->id,
                'notes' => $data['attendance_notes'] ?? null,
            ]);
            $record = DailyRecord::query()->create([
                'student_id' => $student->id,
                'teacher_profile_id' => $teacher->id,
                'halaqa_id' => $halaqa->id,
                'attendance_id' => $attendance->id,
                'record_date' => $date->toDateString(),
                'general_evaluation' => $generalEvaluation,
                'notes' => $data['notes'] ?? null,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            foreach ($validatedItems as $item) {
                $record->recitationItems()->create($item);
            }

            $this->timeline->record(
                $student,
                'daily-record.created',
                'السجل اليومي',
                $record,
                $attendanceStatus->label().' — '.count($validatedItems).' بنود تسميع',
                ['attendance' => $attendanceStatus->value, 'recitation_items_count' => count($validatedItems)],
                $date,
            );
            $this->auditLogger->record('student.daily-record.created', $record, newValues: [
                'student_id' => $student->id,
                'halaqa_id' => $halaqa->id,
                'teacher_profile_id' => $teacher->id,
                'record_date' => $date->toDateString(),
                'attendance' => $attendanceStatus->value,
                'items_count' => count($validatedItems),
            ]);
            $snapshot = $this->progress->snapshot($student);
            $this->alerts->evaluate($student->refresh(), $snapshot);
            $this->achievements->evaluate($student, $snapshot);

            return $record->load(['attendance', 'recitationItems.startAyah.surah', 'recitationItems.endAyah.surah']);
        });
    }

    private function ensureActorCanRecord(User $actor, TeacherProfile $teacher, Halaqa $halaqa, Carbon $date): void
    {
        if ($actor->hasRole('teacher') && $teacher->user_id !== $actor->id) {
            throw ValidationException::withMessages(['teacher' => 'لا يمكنك التسجيل باسم محفظ آخر.']);
        }

        $assigned = $halaqa->teacherAssignments()
            ->where('teacher_profile_id', $teacher->id)
            ->whereDate('starts_at', '<=', $date)
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhereDate('ends_at', '>=', $date))
            ->exists();

        if (! $assigned) {
            throw ValidationException::withMessages(['halaqa_id' => 'المحفظ غير مسند إلى هذه الحلقة في التاريخ المحدد.']);
        }
    }

    private function ensureStudentWasEnrolled(Student $student, Halaqa $halaqa, Carbon $date): void
    {
        $enrolled = $student->enrollments()
            ->where('halaqa_id', $halaqa->id)
            ->whereDate('starts_at', '<=', $date)
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhereDate('ends_at', '>=', $date))
            ->exists();

        if (! $enrolled) {
            throw ValidationException::withMessages(['student_id' => 'الطالب غير ملتحق بهذه الحلقة في التاريخ المحدد.']);
        }
    }
}
