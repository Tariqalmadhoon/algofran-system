<?php

namespace App\Services;

use App\Enums\EvaluationRating;
use App\Models\Attendance;
use App\Models\DailyRecord;
use App\Models\Halaqa;
use App\Models\RecitationItem;
use App\Models\Student;
use App\Models\StudentAlert;
use App\Models\TeacherProfile;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class DashboardMetricsService
{
    public function __construct(
        private readonly StudentVisibilityService $visibility,
        private readonly StudentPeriodRankingService $rankings,
    ) {}

    public function for(User $user, array $filters): array
    {
        try {
            $from = Carbon::parse($filters['date_from'] ?? today()->startOfMonth())->startOfDay();
            $to = Carbon::parse($filters['date_to'] ?? today())->endOfDay();
        } catch (\Throwable) {
            $from = today()->startOfMonth()->startOfDay();
            $to = today()->endOfDay();
        }
        if ($from->gt($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }
        $students = $this->filteredStudents($user, $filters, $from, $to);
        $studentIds = (clone $students)->select('students.id');
        $attendance = Attendance::query()->whereIn('student_id', clone $studentIds)->whereDate('record_date', '>=', $from)->whereDate('record_date', '<=', $to);
        $records = DailyRecord::query()->whereIn('student_id', clone $studentIds)->whereDate('record_date', '>=', $from)->whereDate('record_date', '<=', $to);
        $items = RecitationItem::query()->whereHas('dailyRecord', fn ($query) => $query
            ->whereIn('student_id', clone $studentIds)
            ->whereDate('record_date', '>=', $from)
            ->whereDate('record_date', '<=', $to));

        $attendanceCounts = (clone $attendance)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');
        $present = (int) ($attendanceCounts['present'] ?? 0);
        $late = (int) ($attendanceCounts['late'] ?? 0);
        $absent = (int) ($attendanceCounts['absent'] ?? 0);
        $excused = (int) ($attendanceCounts['excused'] ?? 0);
        $attendanceDenominator = $present + $late + $absent + $excused;

        $activeAlerts = $user->can('alerts.view')
            ? StudentAlert::query()->whereIn('student_id', clone $studentIds)->whereIn('status', ['open', 'acknowledged'])
            : StudentAlert::query()->whereRaw('1 = 0');
        $trendRows = $this->trendRows($studentIds, $to);

        $visibleStudents = (clone $students)->with(['latestProgress.lastMemorizedAyah.surah', 'currentHalaqa:id,name', 'photo:id'])->get();
        $levels = ['excellent' => 0, 'good' => 0, 'needs_support' => 0, 'critical' => 0, 'no_data' => 0];
        foreach ($visibleStudents as $student) {
            $score = $student->latestProgress?->score;
            $level = match (true) {
                $score === null => 'no_data',
                $score >= 80 => 'excellent',
                $score >= 60 => 'good',
                $score >= 40 => 'needs_support',
                default => 'critical',
            };
            $levels[$level]++;
        }
        $studentsWithProgress = $visibleStudents->filter(fn (Student $student) => $student->latestProgress !== null);
        $averageStudentScore = round((float) ($studentsWithProgress->avg(fn (Student $student) => $student->latestProgress->score) ?? 0), 2);

        return [
            'period' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'stats' => [
                'active_students' => (clone $students)->where('status', 'active')->count(),
                'teachers' => $this->teacherCount($user, $filters),
                'halaqas' => $this->halaqaCount($user, $filters),
                'present' => $present + $late,
                'absent' => $absent,
                'daily_records' => (clone $records)->count(),
                'new_memorization' => (clone $items)->where('type', 'new_memorization')->count(),
                'revisions' => (clone $items)->whereIn('type', ['recent_revision', 'old_revision'])->count(),
                'open_alerts' => (clone $activeAlerts)->count(),
                'needs_followup' => (clone $activeAlerts)->distinct()->count('student_id'),
            ],
            'attendance' => [
                'present' => $present,
                'late' => $late,
                'absent' => $absent,
                'excused' => $excused,
                'total' => $present + $late + $absent + $excused,
                'rate' => $attendanceDenominator > 0
                    ? round((($present + $late) / $attendanceDenominator) * 100, 2)
                    : 0,
            ],
            'coverage' => [
                'students_with_progress' => $studentsWithProgress->count(),
                'students_without_progress' => $visibleStudents->count() - $studentsWithProgress->count(),
                'rate' => $visibleStudents->isNotEmpty()
                    ? round(($studentsWithProgress->count() / $visibleStudents->count()) * 100, 2)
                    : 0,
                'average_student_score' => $averageStudentScore,
            ],
            'evaluation_average' => $this->averageEvaluation((clone $items)->pluck('evaluation')),
            'trend' => $this->weeklyTrend($trendRows, $to),
            'monthly_trend' => $this->monthlyTrend($trendRows, $to),
            'levels' => $levels,
            'halaqa_performance' => $this->halaqaPerformance($studentIds, $from, $to),
            'student_rankings' => $this->rankings->calculate($visibleStudents, $from, $to),
            'students' => $visibleStudents->sortBy(fn (Student $student) => $student->latestProgress?->score ?? -1)->take(8)->values(),
            'alerts' => (clone $activeAlerts)->with(['student:id,full_name,first_name,family_name,photo_private_file_id', 'student.photo:id', 'halaqa:id,name'])->orderByRaw("CASE severity WHEN 'critical' THEN 1 WHEN 'warning' THEN 2 ELSE 3 END")->latest('generated_at')->limit(8)->get(),
            'teacher_today' => $this->teacherToday($user),
        ];
    }

    /** @return Builder<Student> */
    private function filteredStudents(User $user, array $filters, Carbon $from, Carbon $to): Builder
    {
        return $this->visibility->queryFor($user)
            ->when($filters['branch_id'] ?? null, fn ($query, $branch) => $query->whereHas('currentHalaqa', fn ($halaqa) => $halaqa->where('branch_id', $branch)))
            ->when($filters['halaqa_id'] ?? null, fn ($query, $halaqa) => $query->where('current_halaqa_id', $halaqa))
            ->when($filters['teacher_id'] ?? null, fn ($query, $teacher) => $query->whereHas('currentHalaqa.teacherAssignments', fn ($assignments) => $assignments
                ->where('teacher_profile_id', $teacher)
                ->whereDate('starts_at', '<=', $to)
                ->where(fn ($dates) => $dates->whereNull('ends_at')->orWhereDate('ends_at', '>=', $from))))
            ->when($filters['student_id'] ?? null, fn ($query, $student) => $query->whereKey($student))
            ->when($filters['program'] ?? null, fn ($query, $program) => $query->whereHas('currentHalaqa', fn ($halaqa) => $halaqa->where('program', $program)));
    }

    private function trendRows(Builder $studentIds, Carbon $to)
    {
        return RecitationItem::query()
            ->join('daily_records', 'daily_records.id', '=', 'recitation_items.daily_record_id')
            ->whereIn('daily_records.student_id', clone $studentIds)
            ->whereDate('daily_records.record_date', '>=', $to->copy()->subMonths(5)->startOfMonth())
            ->whereDate('daily_records.record_date', '<=', $to)
            ->selectRaw("daily_records.record_date as period, SUM(CASE recitation_items.evaluation WHEN 'poor' THEN 25 WHEN 'good' THEN 60 WHEN 'very_good' THEN 80 WHEN 'excellent' THEN 100 ELSE 0 END) as points, COUNT(*) as total")
            ->groupBy('daily_records.record_date')
            ->get();
    }

    private function weeklyTrend($trendRows, Carbon $to): array
    {
        $byDate = $trendRows->keyBy(fn ($row) => Carbon::parse($row->period)->toDateString());
        $points = [];
        for ($offset = 6; $offset >= 0; $offset--) {
            $date = $to->copy()->subDays($offset)->toDateString();
            $row = $byDate->get($date);
            $value = $row && (int) $row->total > 0 ? round((float) $row->points / (int) $row->total, 2) : 0;
            $points[] = ['date' => $date, 'label' => Carbon::parse($date)->translatedFormat('D'), 'value' => $value];
        }

        return $points;
    }

    private function monthlyTrend($trendRows, Carbon $to): array
    {
        $points = [];
        for ($offset = 5; $offset >= 0; $offset--) {
            $month = $to->copy()->subMonths($offset);
            $rows = $trendRows->filter(fn ($row) => Carbon::parse($row->period)->isSameMonth($month));
            $total = (int) $rows->sum('total');
            $value = $total > 0 ? round((float) $rows->sum('points') / $total, 2) : 0;
            $points[] = ['label' => $month->translatedFormat('M Y'), 'value' => $value];
        }

        return $points;
    }

    private function halaqaPerformance(Builder $studentIds, Carbon $from, Carbon $to): array
    {
        return DailyRecord::query()
            ->whereIn('student_id', clone $studentIds)
            ->whereDate('record_date', '>=', $from)
            ->whereDate('record_date', '<=', $to)
            ->with(['halaqa:id,name', 'recitationItems:id,daily_record_id,evaluation'])
            ->get()
            ->groupBy('halaqa_id')
            ->map(function ($records) {
                $values = $records->flatMap->recitationItems->pluck('evaluation')->map(fn ($rating) => $rating->value);

                return [
                    'name' => $records->first()->halaqa->name,
                    'average' => $this->averageEvaluation($values),
                    'records' => $records->count(),
                ];
            })
            ->sortByDesc('average')
            ->take(8)
            ->values()
            ->all();
    }

    private function averageEvaluation($values): float
    {
        return round((float) (collect($values)->map(function ($rating) {
            $value = $rating instanceof EvaluationRating ? $rating->value : (string) $rating;

            return EvaluationRating::tryFrom($value)?->score() ?? 0;
        })->filter()->average() ?? 0), 2);
    }

    private function teacherCount(User $user, array $filters): int
    {
        if ($user->requiresTeacherAssignmentScope()) {
            return $user->teacherProfile?->active ? 1 : 0;
        }

        $centerId = $this->centerIdFor($user);

        return TeacherProfile::query()->where('active', true)
            ->when($centerId, fn ($query) => $query->where('center_id', $centerId))
            ->when(! $user->hasRole('super-admin') && ! $centerId, fn ($query) => $query->whereRaw('1 = 0'))
            ->when($filters['branch_id'] ?? null, fn ($query, $branch) => $query->where('branch_id', $branch))
            ->when($filters['teacher_id'] ?? null, fn ($query, $teacher) => $query->whereKey($teacher))
            ->count();
    }

    private function halaqaCount(User $user, array $filters): int
    {
        $centerId = $this->centerIdFor($user);
        $query = Halaqa::query()->where('active', true)
            ->when($centerId, fn ($query) => $query->where('center_id', $centerId))
            ->when(! $user->hasRole('super-admin') && ! $centerId && ! $user->requiresTeacherAssignmentScope(), fn ($query) => $query->whereRaw('1 = 0'))
            ->when($filters['branch_id'] ?? null, fn ($query, $branch) => $query->where('branch_id', $branch))
            ->when($filters['halaqa_id'] ?? null, fn ($query, $halaqa) => $query->whereKey($halaqa))
            ->when($filters['program'] ?? null, fn ($query, $program) => $query->where('program', $program));
        if ($user->requiresTeacherAssignmentScope()) {
            $query->whereHas('teacherAssignments', fn ($assignments) => $assignments->whereHas('teacher', fn ($teacher) => $teacher->where('user_id', $user->id))->whereNull('ends_at'));
        }

        return $query->count();
    }

    private function centerIdFor(User $user): ?int
    {
        if ($user->hasRole('super-admin') || $user->requiresTeacherAssignmentScope()) {
            return null;
        }

        $centerId = $user->staffProfile?->center_id ?? $user->teacherProfile?->center_id;

        return $centerId ? (int) $centerId : null;
    }

    private function teacherToday(User $user): ?array
    {
        $teacher = $user->teacherProfile;
        if (! $user->can('recitations.create') || ! $teacher?->active) {
            return null;
        }

        $halaqas = Halaqa::query()
            ->whereHas('teacherAssignments', fn ($assignments) => $assignments
                ->where('teacher_profile_id', $teacher->id)
                ->whereDate('starts_at', '<=', today())
                ->where(fn ($dates) => $dates->whereNull('ends_at')->orWhereDate('ends_at', '>=', today())))
            ->withCount(['currentStudents as active_students_count' => fn ($query) => $query->where('status', 'active')])
            ->withCount(['dailyRecords as recorded_today_count' => fn ($query) => $query
                ->where('teacher_profile_id', $teacher->id)
                ->whereDate('record_date', today())])
            ->with('schedules')
            ->get();
        $studentIds = Student::query()->whereIn('current_halaqa_id', $halaqas->pluck('id'))->pluck('id');
        $recorded = $halaqas->sum('recorded_today_count');
        $openAlerts = StudentAlert::query()
            ->whereIn('student_id', $studentIds)
            ->whereIn('status', ['open', 'acknowledged'])
            ->count();

        return [
            'center' => $teacher->center()->first(['id', 'name']),
            'halaqas' => $halaqas,
            'students' => $studentIds->count(),
            'recorded' => $recorded,
            'missing' => max(0, $studentIds->count() - $recorded),
            'open_alerts' => $openAlerts,
        ];
    }
}
