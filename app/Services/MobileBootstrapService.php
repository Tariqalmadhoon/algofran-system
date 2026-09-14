<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Enums\EvaluationRating;
use App\Enums\RecitationType;
use App\Models\DailyRecord;
use App\Models\Halaqa;
use App\Models\HalaqaEnrollment;
use App\Models\QuranAyah;
use App\Models\QuranSurah;
use App\Models\User;

class MobileBootstrapService
{
    public function __construct(
        private readonly MobileSyncCursor $cursor,
        private readonly MobileTeacherScopeService $scope,
        private readonly MobileProfileDataService $profiles,
    ) {}

    public function build(User $user): array
    {
        $teacher = $this->scope->teacher($user);
        $date = today()->toDateString();
        $halaqas = $this->scope->halaqaQuery($user, $date)
            ->with('center:id,name')
            ->orderBy('name')
            ->get(['id', 'center_id', 'name', 'code']);

        $halaqaIds = $halaqas->pluck('id');
        $enrollments = HalaqaEnrollment::query()
            ->whereIn('halaqa_id', $halaqaIds)
            ->whereDate('starts_at', '<=', $date)
            ->where(fn ($dates) => $dates->whereNull('ends_at')->orWhereDate('ends_at', '>=', $date))
            ->whereHas('student', fn ($students) => $students->where('status', 'active'))
            ->with('student:id,student_number,full_name,current_halaqa_id,status')
            ->orderBy('student_id')
            ->get();

        $studentIds = $enrollments->pluck('student_id')->unique();
        $students = $this->profiles->students($studentIds);
        $recordedToday = DailyRecord::query()
            ->whereIn('student_id', $studentIds)
            ->whereDate('record_date', $date)
            ->pluck('id', 'student_id');

        $latestRecord = DailyRecord::query()
            ->where('teacher_profile_id', $teacher->id)
            ->latest('updated_at')
            ->latest('id')
            ->first(['id', 'updated_at']);

        return [
            'schema_version' => 3,
            'server_time' => now()->toISOString(),
            'record_date' => $date,
            'sync_cursor' => $this->cursor->fromRecord($latestRecord),
            'teacher' => $this->profiles->teacher($user),
            'halaqas' => $halaqas->map(fn (Halaqa $halaqa) => [
                'id' => $halaqa->id,
                'name' => $halaqa->name,
                'code' => $halaqa->code,
                'center' => [
                    'id' => $halaqa->center_id,
                    'name' => $halaqa->center?->name,
                ],
                'students' => $enrollments
                    ->where('halaqa_id', $halaqa->id)
                    ->map(function (HalaqaEnrollment $enrollment) use ($recordedToday, $students): array {
                        $student = $students->get($enrollment->student_id);

                        return [
                            'id' => $enrollment->student->id,
                            'student_number' => $enrollment->student->student_number,
                            'full_name' => $enrollment->student->full_name,
                            'updated_at' => $student?->updated_at?->toISOString(),
                            'recorded_today' => $recordedToday->has($enrollment->student_id),
                            'daily_record_id' => $recordedToday->get($enrollment->student_id),
                            'profile' => $student ? $this->profiles->studentArray($student) : null,
                        ];
                    })->values()->all(),
            ])->values()->all(),
            'quran' => [
                'surahs' => QuranSurah::query()->orderBy('id')->get([
                    'id', 'name_arabic', 'verses_count', 'revelation_place',
                ])->toArray(),
                'ayahs' => QuranAyah::query()->orderBy('global_order')->get([
                    'id', 'surah_id', 'ayah_number', 'global_order', 'juz', 'hizb', 'page',
                ])->toArray(),
            ],
            'options' => [
                'attendance_statuses' => $this->enumOptions(AttendanceStatus::cases()),
                'evaluations' => $this->enumOptions(EvaluationRating::cases()),
                'recitation_types' => $this->enumOptions(RecitationType::cases()),
            ],
        ];
    }

    private function enumOptions(array $cases): array
    {
        return array_map(fn ($case) => [
            'value' => $case->value,
            'label' => $case->label(),
        ], $cases);
    }
}
