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
    public function __construct(private readonly StudentVisibilityService $visibility) {}

    public function for(User $user, array $filters): array
    {
        try {
            $from = Carbon::parse($filters['date_from'] ?? today()->subDays(29))->startOfDay();
            $to = Carbon::parse($filters['date_to'] ?? today())->endOfDay();
        } catch (\Throwable) {
            $from = today()->subDays(29)->startOfDay();
            $to = today()->endOfDay();
        }
        if ($from->gt($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }
        $students = $this->filteredStudents($user, $filters);
        $studentIds = (clone $students)->select('students.id');
        $attendance = Attendance::query()->whereIn('student_id', clone $studentIds)->whereDate('record_date', '>=', $from)->whereDate('record_date', '<=', $to);
        $records = DailyRecord::query()->whereIn('student_id', clone $studentIds)->whereDate('record_date', '>=', $from)->whereDate('record_date', '<=', $to);
        $items = RecitationItem::query()->whereHas('dailyRecord', fn ($query) => $query
            ->whereIn('student_id', clone $studentIds)
            ->whereDate('record_date', '>=', $from)
            ->whereDate('record_date', '<=', $to));

        $activeAlerts = $user->can('alerts.view')
            ? StudentAlert::query()->whereIn('student_id', clone $studentIds)->whereIn('status', ['open', 'acknowledged'])
            : StudentAlert::query()->whereRaw('1 = 0');
        $trendRows = $this->trendRows($studentIds, $to);

        $visibleStudents = (clone $students)->with(['latestProgress.lastMemorizedAyah.surah', 'currentHalaqa:id,name'])->get();
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

        return [
            'period' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'stats' => [
                'active_students' => (clone $students)->where('status', 'active')->count(),
                'teachers' => $this->teacherCount($user, $filters),
                'halaqas' => $this->halaqaCount($user, $filters),
                'present' => (clone $attendance)->whereIn('status', ['present', 'late'])->count(),
                'absent' => (clone $attendance)->where('status', 'absent')->count(),
                'daily_records' => (clone $records)->count(),
                'new_memorization' => (clone $items)->where('type', 'new_memorization')->count(),
                'revisions' => (clone $items)->whereIn('type', ['recent_revision', 'old_revision'])->count(),
                'open_alerts' => (clone $activeAlerts)->count(),
                'needs_followup' => (clone $activeAlerts)->distinct()->count('student_id'),
            ],
            'evaluation_average' => $this->averageEvaluation((clone $items)->pluck('evaluation')),
            'trend' => $this->weeklyTrend($trendRows, $to),
            'monthly_trend' => $this->monthlyTrend($trendRows, $to),
            'levels' => $levels,
            'halaqa_performance' => $this->halaqaPerformance($studentIds, $from, $to),
            'students' => $visibleStudents->sortBy(fn (Student $student) => $student->latestProgress?->score ?? -1)->take(8)->values(),
            'alerts' => (clone $activeAlerts)->with(['student:id,full_name', 'halaqa:id,name'])->orderByRaw("CASE severity WHEN 'critical' THEN 1 WHEN 'warning' THEN 2 ELSE 3 END")->latest('generated_at')->limit(8)->get(),
            'teacher_today' => $this->teacherToday($user),
        ];
    }

    /** @return Builder<Student> */
    private function filteredStudents(User $user, array $filters): Builder
    {
        return $this->visibility->queryFor($user)
            ->when($filters['branch_id'] ?? null, fn ($query, $branch) => $query->whereHas('currentHalaqa', fn ($halaqa) => $halaqa->where('branch_id', $branch)))
            ->when($filters['halaqa_id'] ?? null, fn ($query, $halaqa) => $query->where('current_halaqa_id', $halaqa))
            ->when($filters['teacher_id'] ?? null, fn ($query, $teacher) => $query->whereHas('currentHalaqa.teacherAssignments', fn ($assignments) => $assignments->where('teacher_profile_id', $teacher)->whereNull('ends_at')))
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
        if ($user->hasRole('teacher')) {
            return $user->teacherProfile?->active ? 1 : 0;
        }

        return TeacherProfile::query()->where('active', true)
            ->when($filters['branch_id'] ?? null, fn ($query, $branch) => $query->where('branch_id', $branch))
            ->when($filters['teacher_id'] ?? null, fn ($query, $teacher) => $query->whereKey($teacher))
            ->count();
    }

    private function halaqaCount(User $user, array $filters): int
    {
        $query = Halaqa::query()->where('active', true)
            ->when($filters['branch_id'] ?? null, fn ($query, $branch) => $query->where('branch_id', $branch))
            ->when($filters['halaqa_id'] ?? null, fn ($query, $halaqa) => $query->whereKey($halaqa))
            ->when($filters['program'] ?? null, fn ($query, $program) => $query->where('program', $program));
        if ($user->hasRole('teacher')) {
            $query->whereHas('teacherAssignments', fn ($assignments) => $assignments->whereHas('teacher', fn ($teacher) => $teacher->where('user_id', $user->id))->whereNull('ends_at'));
        }

        return $query->count();
    }

    private function teacherToday(User $user): ?array
    {
        if (! $user->hasRole('teacher') || ! $user->teacherProfile) {
            return null;
        }

        $halaqas = Halaqa::query()
            ->whereHas('teacherAssignments', fn ($assignments) => $assignments
                ->where('teacher_profile_id', $user->teacherProfile->id)
                ->whereDate('starts_at', '<=', today())
                ->where(fn ($dates) => $dates->whereNull('ends_at')->orWhereDate('ends_at', '>=', today())))
            ->withCount(['currentStudents as active_students_count' => fn ($query) => $query->where('status', 'active')])
            ->get();
        $studentIds = Student::query()->whereIn('current_halaqa_id', $halaqas->pluck('id'))->pluck('id');
        $recorded = DailyRecord::query()->whereIn('student_id', $studentIds)->whereDate('record_date', today())->count();

        return [
            'halaqas' => $halaqas,
            'students' => $studentIds->count(),
            'recorded' => $recorded,
            'missing' => max(0, $studentIds->count() - $recorded),
        ];
    }
}
