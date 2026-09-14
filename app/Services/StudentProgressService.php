<?php

namespace App\Services;

use App\Enums\EvaluationRating;
use App\Enums\RecitationType;
use App\Models\QuranAyah;
use App\Models\RecitationItem;
use App\Models\Student;
use App\Models\StudentProgressSnapshot;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StudentProgressService
{
    private const TOTAL_AYAHS = 6236;

    public function __construct(
        private readonly SettingsService $settings,
        private readonly MemorizationJourneyService $memorizationJourney,
    ) {}

    public function snapshot(Student $student, CarbonInterface|string|null $asOf = null): StudentProgressSnapshot
    {
        $date = Carbon::parse($asOf ?? today())->endOfDay();
        $metrics = $this->calculate($student, $date);

        $snapshot = StudentProgressSnapshot::query()
            ->where('student_id', $student->id)
            ->whereDate('as_of_date', $date)
            ->first();
        if ($snapshot) {
            $snapshot->fill($metrics)->save();
        } else {
            $snapshot = StudentProgressSnapshot::query()->create([
                'student_id' => $student->id,
                'as_of_date' => $date->toDateString(),
                ...$metrics,
            ]);
        }

        return $snapshot->load('lastMemorizedAyah.surah');
    }

    public function calculate(Student $student, CarbonInterface|string|null $asOf = null): array
    {
        $date = Carbon::parse($asOf ?? today())->endOfDay();
        $intervals = $this->memorizedIntervals($student, $date);
        $memorizedAyahs = (int) collect($intervals)->sum(fn (array $range) => $range[1] - $range[0] + 1);
        $lastGlobalOrder = $intervals === [] ? null : max(array_column($intervals, 1));
        $lastAyahId = $lastGlobalOrder
            ? QuranAyah::query()->where('global_order', $lastGlobalOrder)->value('id')
            : null;

        $surahRanges = QuranAyah::query()
            ->select('surah_id', DB::raw('MIN(global_order) as range_start'), DB::raw('MAX(global_order) as range_end'))
            ->groupBy('surah_id')
            ->get();
        $juzRanges = QuranAyah::query()
            ->select('juz', DB::raw('MIN(global_order) as range_start'), DB::raw('MAX(global_order) as range_end'))
            ->groupBy('juz')
            ->get();
        $completedSurahs = $surahRanges->filter(fn ($range) => $this->isCovered($intervals, (int) $range->range_start, (int) $range->range_end))->count();
        $strictCompletedJuz = $juzRanges->filter(fn ($range) => $this->isCovered($intervals, (int) $range->range_start, (int) $range->range_end))->count();
        $journey = $this->memorizationJourney->calculate($student, $date);
        $completedJuz = max($strictCompletedJuz, (int) $journey['completed_juz']);

        $itemQuery = RecitationItem::query()
            ->whereHas('dailyRecord', fn ($query) => $query->where('student_id', $student->id)->whereDate('record_date', '<=', $date));
        $memorizationSessions = (clone $itemQuery)->where('type', RecitationType::NewMemorization->value)->distinct()->count('daily_record_id');
        $revisionTypes = [RecitationType::RecentRevision->value, RecitationType::OldRevision->value];
        $revisionSessions = (clone $itemQuery)->whereIn('type', $revisionTypes)->distinct()->count('daily_record_id');
        $lastRevisionAt = $student->dailyRecords()
            ->whereDate('record_date', '<=', $date)
            ->whereHas('recitationItems', fn ($query) => $query->whereIn('type', $revisionTypes))
            ->max('record_date');
        $lastMemorizationAt = $student->dailyRecords()
            ->whereDate('record_date', '<=', $date)
            ->whereHas('recitationItems', fn ($query) => $query->where('type', RecitationType::NewMemorization->value))
            ->max('record_date');

        $evaluationValues = (clone $itemQuery)
            ->join('daily_records', 'daily_records.id', '=', 'recitation_items.daily_record_id')
            ->orderByDesc('daily_records.record_date')
            ->orderByDesc('recitation_items.id')
            ->pluck('recitation_items.evaluation')
            ->map(fn ($rating) => $rating instanceof EvaluationRating ? $rating->score() : EvaluationRating::from((string) $rating)->score());
        $evaluationAverage = round((float) ($evaluationValues->average() ?? 0), 2);
        $recentAverage = round((float) ($evaluationValues->take(5)->average() ?? 0), 2);
        $previousAverage = round((float) ($evaluationValues->slice(5, 5)->average() ?? $recentAverage), 2);
        $trend = round($recentAverage - $previousAverage, 2);

        $attendanceCounts = $student->attendances()
            ->whereDate('record_date', '<=', $date)
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');
        $present = (int) ($attendanceCounts['present'] ?? 0);
        $late = (int) ($attendanceCounts['late'] ?? 0);
        $absent = (int) ($attendanceCounts['absent'] ?? 0);
        $excused = (int) ($attendanceCounts['excused'] ?? 0);
        $attendanceDenominator = $present + $late + $absent + $excused;
        $attendanceRate = $attendanceDenominator > 0 ? round((($present + $late) / $attendanceDenominator) * 100, 2) : 0.0;

        $periodStart = $date->copy()->subDays(29)->startOfDay();
        $monthlyMemorizationSessions = $this->sessionCount($student, [RecitationType::NewMemorization->value], $periodStart, $date);
        $monthlyRevisionSessions = $this->sessionCount($student, $revisionTypes, $periodStart, $date);
        $memorizationTarget = max(1, (int) $this->settings->get('monthly_memorization_sessions_target', config('system.academic.monthly_memorization_sessions_target'), 'academic'));
        $revisionTarget = max(1, (int) $this->settings->get('monthly_revision_sessions_target', config('system.academic.monthly_revision_sessions_target'), 'academic'));

        $factors = [
            'recitation_quality' => $evaluationAverage,
            'revision_adherence' => min(100, round(($monthlyRevisionSessions / $revisionTarget) * 100, 2)),
            'attendance' => $attendanceRate,
            'target_achievement' => min(100, round(($monthlyMemorizationSessions / $memorizationTarget) * 100, 2)),
            'improvement_trend' => max(0, min(100, round(50 + $trend, 2))),
        ];
        $weights = $this->scoreWeights();
        $weightTotal = max(1, array_sum($weights));
        $score = 0.0;
        foreach ($factors as $key => $value) {
            $score += $value * (($weights[$key] ?? 0) / $weightTotal);
        }
        $score = round(max(0, min(100, $score)), 2);

        return [
            'last_memorized_ayah_id' => $lastAyahId,
            'memorized_ayahs' => $memorizedAyahs,
            'memorized_percentage' => round(($memorizedAyahs / self::TOTAL_AYAHS) * 100, 3),
            'completed_surahs' => $completedSurahs,
            'completed_juz' => $completedJuz,
            'memorization_sessions' => $memorizationSessions,
            'revision_sessions' => $revisionSessions,
            'last_revision_at' => $lastRevisionAt,
            'evaluation_average' => $evaluationAverage,
            'performance_trend' => $trend,
            'attendance_rate' => $attendanceRate,
            'score' => $score,
            'score_breakdown' => [
                'weights' => $weights,
                'factors' => $factors,
                'reasons' => $this->scoreReasons($factors, $evaluationValues->count()),
            ],
            'metrics' => [
                'attendance' => compact('present', 'late', 'absent', 'excused'),
                'evaluation_count' => $evaluationValues->count(),
                'recent_evaluation_average' => $recentAverage,
                'previous_evaluation_average' => $previousAverage,
                'monthly_memorization_sessions' => $monthlyMemorizationSessions,
                'monthly_revision_sessions' => $monthlyRevisionSessions,
                'memorization_target' => $memorizationTarget,
                'revision_target' => $revisionTarget,
                'last_memorization_at' => $lastMemorizationAt,
                'days_since_last_memorization' => $lastMemorizationAt ? Carbon::parse($lastMemorizationAt)->startOfDay()->diffInDays($date->copy()->startOfDay()) : null,
                'days_since_last_revision' => $lastRevisionAt ? Carbon::parse($lastRevisionAt)->startOfDay()->diffInDays($date->copy()->startOfDay()) : null,
                'strict_completed_juz' => $strictCompletedJuz,
                'memorization_journey' => $journey,
            ],
        ];
    }

    /** @return array<int, array{0: int, 1: int}> */
    private function memorizedIntervals(Student $student, CarbonInterface $date): array
    {
        $ranges = $student->baselines()
            ->whereDate('recorded_at', '<=', $date)
            ->with(['startAyah:id,global_order', 'endAyah:id,global_order'])
            ->get()
            ->map(fn ($baseline) => [$baseline->startAyah->global_order, $baseline->endAyah->global_order]);
        $dailyRanges = RecitationItem::query()
            ->where('type', RecitationType::NewMemorization->value)
            ->whereHas('dailyRecord', fn ($query) => $query->where('student_id', $student->id)->whereDate('record_date', '<=', $date))
            ->with(['startAyah:id,global_order', 'endAyah:id,global_order'])
            ->get()
            ->map(fn ($item) => [$item->startAyah->global_order, $item->endAyah->global_order]);

        return $this->mergeIntervals($ranges->concat($dailyRanges));
    }

    /** @param Collection<int, array{0: int, 1: int}> $ranges */
    private function mergeIntervals(Collection $ranges): array
    {
        $sorted = $ranges->sortBy(fn (array $range) => $range[0])->values();
        $merged = [];
        foreach ($sorted as [$start, $end]) {
            if ($merged === [] || $start > $merged[array_key_last($merged)][1] + 1) {
                $merged[] = [(int) $start, (int) $end];

                continue;
            }
            $last = array_key_last($merged);
            $merged[$last][1] = max($merged[$last][1], (int) $end);
        }

        return $merged;
    }

    private function isCovered(array $intervals, int $start, int $end): bool
    {
        foreach ($intervals as [$rangeStart, $rangeEnd]) {
            if ($rangeStart <= $start && $rangeEnd >= $end) {
                return true;
            }
        }

        return false;
    }

    private function sessionCount(Student $student, array $types, CarbonInterface $from, CarbonInterface $to): int
    {
        return RecitationItem::query()
            ->whereIn('type', $types)
            ->whereHas('dailyRecord', fn ($query) => $query
                ->where('student_id', $student->id)
                ->whereDate('record_date', '>=', $from)
                ->whereDate('record_date', '<=', $to))
            ->distinct()
            ->count('daily_record_id');
    }

    private function scoreWeights(): array
    {
        $configured = $this->settings->get('score_weights', config('system.academic.score_weights'), 'academic');

        return is_array($configured) ? $configured : config('system.academic.score_weights');
    }

    private function scoreReasons(array $factors, int $evaluationCount): array
    {
        $reasons = [];
        if ($evaluationCount === 0) {
            $reasons[] = 'لا توجد تقييمات تسميع كافية بعد.';
        }
        if ($factors['recitation_quality'] < 60 && $evaluationCount > 0) {
            $reasons[] = 'متوسط جودة التسميع أقل من المستوى المطلوب.';
        }
        if ($factors['revision_adherence'] < 60) {
            $reasons[] = 'جلسات المراجعة خلال آخر 30 يومًا أقل من الهدف.';
        }
        if ($factors['attendance'] < 80) {
            $reasons[] = 'نسبة الحضور أقل من 80%.';
        }
        if ($factors['target_achievement'] < 60) {
            $reasons[] = 'تحقيق هدف الحفظ الشهري أقل من 60%.';
        }
        if ($factors['improvement_trend'] < 50) {
            $reasons[] = 'اتجاه التقييمات الأخيرة متراجع.';
        }

        return $reasons ?: ['الأداء متوازن وفق البيانات المسجلة.'];
    }
}
