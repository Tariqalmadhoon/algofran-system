<?php

namespace App\Services;

use App\Models\DailyRecord;
use App\Models\Student;
use App\Models\StudentProgressSnapshot;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class MobileProfileDataService
{
    public const RELATED_ITEMS_LIMIT = 12;

    public function __construct(private readonly MobileTeacherScopeService $scope) {}

    public function teacher(User $user): array
    {
        $teacher = $this->scope->teacher($user)->loadMissing('center:id,name');
        $halaqaIds = $this->scope->halaqaQuery($user)->pluck('id');
        $studentIds = $this->scope->studentQuery($user)->pluck('id');
        $hasActiveAssignment = $halaqaIds->isNotEmpty();

        return [
            'id' => $teacher->id,
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'employee_number' => $teacher->employee_number,
            'specialization' => $teacher->specialization,
            'hired_at' => $teacher->hired_at?->toDateString(),
            'center' => $teacher->center ? [
                'id' => $teacher->center->id,
                'name' => $teacher->center->name,
            ] : null,
            'roles' => $user->getRoleNames()->values()->all(),
            'can_export_reports' => $user->can('recitations.export'),
            'can_create_students' => $hasActiveAssignment && $user->can('students.create'),
            'can_update_students' => $hasActiveAssignment && $user->can('students.update'),
            'can_archive_students' => $hasActiveAssignment && $user->can('students.archive'),
            'summary' => [
                'halaqas_count' => $halaqaIds->count(),
                'students_count' => $studentIds->count(),
                'recorded_today' => DailyRecord::query()
                    ->whereIn('halaqa_id', $halaqaIds)
                    ->whereIn('student_id', $studentIds)
                    ->whereDate('record_date', today())
                    ->distinct()
                    ->count('student_id'),
            ],
        ];
    }

    public function student(User $user, Student $student): array
    {
        abort_unless($this->scope->canAccessStudent($user, $student), 403);

        $loadedStudent = $this->studentQuery()
            ->whereKey($student)
            ->firstOrFail();

        return $this->studentArray($loadedStudent);
    }

    /**
     * @param  Collection<int, int>|array<int, int>  $studentIds
     * @return Collection<int, Student>
     */
    public function students(Collection|array $studentIds): Collection
    {
        return $this->studentQuery()
            ->whereIn('id', $studentIds)
            ->get()
            ->keyBy('id');
    }

    public function studentArray(Student $student): array
    {
        /** @var StudentProgressSnapshot|null $progress */
        $progress = $student->latestProgress;
        $lastAyah = $progress?->lastMemorizedAyah;

        return [
            'first_name' => $student->first_name,
            'father_name' => $student->father_name,
            'grandfather_name' => $student->grandfather_name,
            'family_name' => $student->family_name,
            'status' => $student->status->value,
            'status_label' => $student->status->label(),
            'birth_date' => $student->birth_date?->toDateString(),
            'contact_phone' => $student->contact_phone,
            'registration_date' => $student->registration_date?->toDateString(),
            'notes' => $student->notes,
            'updated_at' => $student->updated_at?->toISOString(),
            'progress' => $progress ? [
                'as_of_date' => $progress->as_of_date?->toDateString(),
                'memorized_ayahs' => $progress->memorized_ayahs,
                'memorized_percentage' => $progress->memorized_percentage,
                'completed_surahs' => $progress->completed_surahs,
                'completed_juz' => $progress->completed_juz,
                'memorization_sessions' => $progress->memorization_sessions,
                'revision_sessions' => $progress->revision_sessions,
                'last_revision_at' => $progress->last_revision_at?->toDateString(),
                'evaluation_average' => $progress->evaluation_average,
                'performance_trend' => $progress->performance_trend,
                'attendance_rate' => $progress->attendance_rate,
                'score' => $progress->score,
                'last_memorized_position' => $lastAyah ? [
                    'surah_id' => $lastAyah->surah_id,
                    'surah_name' => $lastAyah->surah?->name_arabic,
                    'ayah_number' => $lastAyah->ayah_number,
                    'juz' => $lastAyah->juz,
                ] : null,
                'memorization_journey' => $progress->metrics['memorization_journey'] ?? null,
            ] : null,
            'achievements' => $student->achievements->map(fn ($achievement) => [
                'id' => $achievement->id,
                'type' => $achievement->type->value,
                'type_label' => $achievement->type->label(),
                'title' => $achievement->title,
                'description' => $achievement->description,
                'achieved_at' => $achievement->achieved_at?->toDateString(),
                'issuer' => $achievement->issuer,
            ])->values()->all(),
            'recent_records' => $student->dailyRecords->map(fn (DailyRecord $record) => [
                'id' => $record->id,
                'record_date' => $record->record_date?->toDateString(),
                'general_evaluation' => $record->general_evaluation?->value,
                'general_evaluation_label' => $record->general_evaluation?->label(),
                'notes' => $record->notes,
                'attendance' => $record->attendance ? [
                    'status' => $record->attendance->status->value,
                    'status_label' => $record->attendance->status->label(),
                ] : null,
                'recitations' => $record->recitationItems->map(fn ($item) => [
                    'id' => $item->id,
                    'type' => $item->type->value,
                    'type_label' => $item->type->label(),
                    'evaluation' => $item->evaluation->value,
                    'evaluation_label' => $item->evaluation->label(),
                    'start' => [
                        'ayah_id' => $item->start_ayah_id,
                        'surah_id' => $item->startAyah->surah_id,
                        'surah_name' => $item->startAyah->surah?->name_arabic,
                        'ayah_number' => $item->startAyah->ayah_number,
                    ],
                    'end' => [
                        'ayah_id' => $item->end_ayah_id,
                        'surah_id' => $item->endAyah->surah_id,
                        'surah_name' => $item->endAyah->surah?->name_arabic,
                        'ayah_number' => $item->endAyah->ayah_number,
                    ],
                    'memorization_errors' => $item->memorization_errors,
                    'tajweed_errors' => $item->tajweed_errors,
                    'hesitation_count' => $item->hesitation_count,
                    'teacher_prompt_count' => $item->teacher_prompt_count,
                    'notes' => $item->notes,
                ])->values()->all(),
            ])->values()->all(),
        ];
    }

    /** @return Builder<Student> */
    private function studentQuery(): Builder
    {
        return Student::query()->with([
            'latestProgress.lastMemorizedAyah.surah:id,name_arabic',
            'achievements' => fn ($query) => $query
                ->latest('achieved_at')
                ->latest('id')
                ->limit(self::RELATED_ITEMS_LIMIT),
            'dailyRecords' => fn ($query) => $query
                ->latest('record_date')
                ->latest('id')
                ->limit(self::RELATED_ITEMS_LIMIT),
            'dailyRecords.attendance',
            'dailyRecords.recitationItems.startAyah.surah:id,name_arabic',
            'dailyRecords.recitationItems.endAyah.surah:id,name_arabic',
        ]);
    }
}
