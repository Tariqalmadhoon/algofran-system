<?php

namespace App\Services;

use App\Enums\EvaluationRating;
use App\Enums\RecitationType;
use App\Models\Attendance;
use App\Models\RecitationItem;
use App\Models\Student;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class StudentPeriodRankingService
{
    private const QURAN_AYAHS = 6236;

    private const QURAN_JUZ = 30;

    /**
     * Build a transparent ranking for the supplied, already-authorized students.
     * Memorization quantity is based on the union of unique Ayahs recorded as new
     * memorization in the period, so overlapping entries are never counted twice.
     *
     * @param  Collection<int, Student>  $students
     */
    public function calculate(Collection $students, CarbonInterface $from, CarbonInterface $to): array
    {
        if ($students->isEmpty()) {
            return $this->emptyResult($from, $to);
        }

        $studentIds = $students->pluck('id')->map(fn ($id) => (int) $id)->all();
        $students = Student::query()->whereKey($studentIds)->with([
            'photo:id',
            'currentHalaqa:id,name,primary_teacher_id',
            'currentHalaqa.primaryTeacher:id,user_id',
            'currentHalaqa.primaryTeacher.user:id,name',
            'currentHalaqa.teacherAssignments.teacher.user:id,name',
        ])->get();
        $attendance = Attendance::query()
            ->whereIn('student_id', $studentIds)
            ->whereDate('record_date', '>=', $from)
            ->whereDate('record_date', '<=', $to)
            ->selectRaw("student_id,
                SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) AS present_count,
                SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) AS late_count,
                SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) AS absent_count,
                SUM(CASE WHEN status = 'excused' THEN 1 ELSE 0 END) AS excused_count,
                COUNT(*) AS total_count")
            ->groupBy('student_id')
            ->get()
            ->keyBy(fn ($row) => (int) $row->student_id);

        $memorizationRows = RecitationItem::query()
            ->join('daily_records', 'daily_records.id', '=', 'recitation_items.daily_record_id')
            ->join('attendances', 'attendances.id', '=', 'daily_records.attendance_id')
            ->join('quran_ayahs as start_ayahs', 'start_ayahs.id', '=', 'recitation_items.start_ayah_id')
            ->join('quran_ayahs as end_ayahs', 'end_ayahs.id', '=', 'recitation_items.end_ayah_id')
            ->whereIn('daily_records.student_id', $studentIds)
            ->where('recitation_items.type', RecitationType::NewMemorization->value)
            ->whereIn('attendances.status', ['present', 'late'])
            ->whereDate('daily_records.record_date', '>=', $from)
            ->whereDate('daily_records.record_date', '<=', $to)
            ->select([
                'daily_records.student_id',
                'daily_records.id as daily_record_id',
                'recitation_items.evaluation',
                'start_ayahs.global_order as start_order',
                'end_ayahs.global_order as end_order',
            ])
            ->get()
            ->groupBy(fn ($row) => (int) $row->student_id);

        $rows = $students->map(function (Student $student) use ($attendance, $memorizationRows) {
            $attendanceRow = $attendance->get($student->id);
            $present = (int) ($attendanceRow?->present_count ?? 0);
            $late = (int) ($attendanceRow?->late_count ?? 0);
            $absent = (int) ($attendanceRow?->absent_count ?? 0);
            $excused = (int) ($attendanceRow?->excused_count ?? 0);
            $total = (int) ($attendanceRow?->total_count ?? 0);
            $commitmentRate = $total > 0
                ? round((($present + $late) / $total) * 100, 1)
                : null;

            $studentItems = $memorizationRows->get($student->id, collect());
            $intervals = $studentItems
                ->map(fn ($item) => [(int) $item->start_order, (int) $item->end_order])
                ->all();
            $memorizedAyahs = $this->uniqueAyahCount($intervals);
            $evaluationAverage = $this->evaluationAverage($studentItems->pluck('evaluation'));

            return [
                'student' => $student,
                'halaqa_name' => $student->currentHalaqa?->name ?? 'غير مسند',
                'teacher_name' => $this->teacherName($student),
                'memorized_ayahs' => $memorizedAyahs,
                'equivalent_juz' => round(($memorizedAyahs / self::QURAN_AYAHS) * self::QURAN_JUZ, 2),
                'memorization_sessions' => $studentItems->pluck('daily_record_id')->unique()->count(),
                'evaluation_average' => $evaluationAverage,
                'present_days' => $present,
                'late_days' => $late,
                'absent_days' => $absent,
                'excused_days' => $excused,
                'attendance_days' => $total,
                'commitment_rate' => $commitmentRate,
                'commitment_status' => $this->commitmentStatus($commitmentRate, $absent, $excused),
            ];
        });

        $memorizationSorted = $rows->sort($this->memorizationComparator(...))->values();
        $memorizationRanks = $memorizationSorted
            ->mapWithKeys(fn (array $row, int $index) => [$row['student']->id => $index + 1]);

        $commitmentSorted = $rows
            ->filter(fn (array $row) => $row['commitment_rate'] !== null)
            ->sort($this->commitmentComparator(...))
            ->values();
        $commitmentRanks = $commitmentSorted
            ->mapWithKeys(fn (array $row, int $index) => [$row['student']->id => $index + 1]);

        $rankings = $memorizationSorted->map(function (array $row, int $index) use ($memorizationRanks, $commitmentRanks) {
            $studentId = $row['student']->id;
            $row['rank'] = $index + 1;
            $row['memorization_rank'] = $memorizationRanks->get($studentId);
            $row['commitment_rank'] = $commitmentRanks->get($studentId);

            return $row;
        })->values();

        $withAttendance = $rows->whereNotNull('commitment_rate');
        $topMemorizer = $memorizationSorted->first(fn (array $row) => $row['memorized_ayahs'] > 0);
        $mostCommitted = $commitmentSorted->first();

        return [
            'period' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'rankings' => $rankings,
            'top_memorizer' => $topMemorizer,
            'most_committed' => $mostCommitted,
            'summary' => [
                'students' => $rows->count(),
                'students_with_attendance' => $withAttendance->count(),
                'memorized_ayahs' => (int) $rows->sum('memorized_ayahs'),
                'equivalent_juz' => round((float) $rows->sum('equivalent_juz'), 2),
                'average_commitment' => $withAttendance->isNotEmpty()
                    ? round((float) $withAttendance->avg('commitment_rate'), 1)
                    : null,
                'absent_days' => (int) $rows->sum('absent_days'),
                'excused_days' => (int) $rows->sum('excused_days'),
            ],
        ];
    }

    /** @param array<int, array{0:int, 1:int}> $intervals */
    private function uniqueAyahCount(array $intervals): int
    {
        if ($intervals === []) {
            return 0;
        }

        usort($intervals, fn (array $left, array $right) => $left[0] <=> $right[0]);
        $merged = [];
        foreach ($intervals as [$start, $end]) {
            if ($start > $end) {
                [$start, $end] = [$end, $start];
            }

            $lastIndex = count($merged) - 1;
            if ($lastIndex < 0 || $start > $merged[$lastIndex][1] + 1) {
                $merged[] = [$start, $end];

                continue;
            }

            $merged[$lastIndex][1] = max($merged[$lastIndex][1], $end);
        }

        return array_sum(array_map(fn (array $range) => $range[1] - $range[0] + 1, $merged));
    }

    private function evaluationAverage(Collection $ratings): ?float
    {
        $scores = $ratings
            ->map(fn ($rating) => $rating instanceof EvaluationRating
                ? $rating->score()
                : EvaluationRating::tryFrom((string) $rating)?->score())
            ->filter(fn ($score) => $score !== null);

        return $scores->isEmpty() ? null : round((float) $scores->average(), 1);
    }

    private function teacherName(Student $student): string
    {
        $primary = $student->currentHalaqa?->primaryTeacher?->user?->name;
        if ($primary) {
            return $primary;
        }

        return $student->currentHalaqa?->teacherAssignments
            ?->sortBy(fn ($assignment) => $assignment->role === 'primary' ? 0 : 1)
            ->first()?->teacher?->user?->name ?? 'غير مسند';
    }

    private function commitmentStatus(?float $rate, int $absent, int $excused): string
    {
        return match (true) {
            $rate === null => 'no_data',
            $absent > 0 => 'needs_contact',
            $excused > 3 => 'over_excused',
            $rate >= 90 => 'excellent',
            $rate >= 75 => 'good',
            default => 'needs_followup',
        };
    }

    private function memorizationComparator(array $left, array $right): int
    {
        return ($right['memorized_ayahs'] <=> $left['memorized_ayahs'])
            ?: (($right['commitment_rate'] ?? -1) <=> ($left['commitment_rate'] ?? -1))
            ?: strcmp($left['student']->full_name, $right['student']->full_name);
    }

    private function commitmentComparator(array $left, array $right): int
    {
        return ($right['commitment_rate'] <=> $left['commitment_rate'])
            ?: ($left['absent_days'] <=> $right['absent_days'])
            ?: ($left['excused_days'] <=> $right['excused_days'])
            ?: ($right['attendance_days'] <=> $left['attendance_days'])
            ?: ($right['memorized_ayahs'] <=> $left['memorized_ayahs'])
            ?: strcmp($left['student']->full_name, $right['student']->full_name);
    }

    private function emptyResult(CarbonInterface $from, CarbonInterface $to): array
    {
        return [
            'period' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'rankings' => collect(),
            'top_memorizer' => null,
            'most_committed' => null,
            'summary' => [
                'students' => 0,
                'students_with_attendance' => 0,
                'memorized_ayahs' => 0,
                'equivalent_juz' => 0.0,
                'average_commitment' => null,
                'absent_days' => 0,
                'excused_days' => 0,
            ],
        ];
    }
}
