<?php

namespace App\Services;

use App\Enums\AlertSeverity;
use App\Enums\AlertStatus;
use App\Enums\EvaluationRating;
use App\Models\Halaqa;
use App\Models\RecitationItem;
use App\Models\Student;
use App\Models\StudentAlert;
use App\Models\StudentProgressSnapshot;
use App\Models\User;
use App\Notifications\SystemNotification;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Log;
use Throwable;

class StudentAlertEngine
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly AuditLogger $auditLogger,
        private readonly StudentVisibilityService $visibility,
    ) {}

    /** @return array<int, StudentAlert> */
    public function evaluate(
        Student $student,
        StudentProgressSnapshot $snapshot,
        CarbonInterface|string|null $asOf = null,
    ): array {
        $alerts = [];
        $evaluationDate = Carbon::parse($asOf ?? $snapshot->as_of_date ?? today())->endOfDay();
        $evaluations = RecitationItem::query()
            ->whereHas('dailyRecord', fn ($query) => $query->where('student_id', $student->id))
            ->join('daily_records', 'daily_records.id', '=', 'recitation_items.daily_record_id')
            ->latest('daily_records.record_date')
            ->latest('recitation_items.id')
            ->limit(5)
            ->pluck('recitation_items.evaluation')
            ->map(fn ($rating) => $rating instanceof EvaluationRating ? $rating->score() : EvaluationRating::from((string) $rating)->score());

        if ($evaluations->take(2)->count() === 2 && $evaluations->take(2)->every(fn (int $score) => $score <= 25)) {
            $alerts[] = $this->raise($student, 'consecutive_poor_evaluations', AlertSeverity::Critical, 'تقييم ضعيف في آخر جلستين متتاليتين.', ['scores' => $evaluations->take(2)->values()->all()]);
        } elseif ($evaluations->filter(fn (int $score) => $score <= 25)->count() >= 3) {
            $alerts[] = $this->raise($student, 'frequent_poor_evaluations', AlertSeverity::Warning, 'ثلاثة تقييمات ضعيفة ضمن آخر خمس جلسات.', ['scores' => $evaluations->values()->all()]);
        }

        if (($snapshot->metrics['evaluation_count'] ?? 0) >= 6 && $snapshot->performance_trend <= -15) {
            $alerts[] = $this->raise($student, 'performance_decline', AlertSeverity::Critical, 'انخفض متوسط التقييمات الأخيرة بمقدار '.abs($snapshot->performance_trend).' نقطة.', ['trend' => $snapshot->performance_trend]);
        }

        array_push($alerts, ...$this->monthlyAttendanceAlerts($student, $evaluationDate));

        $revisionDelay = (int) $this->settings->get('revision_delay_days', config('system.academic.revision_delay_days'), 'academic');
        $daysSinceRevision = $snapshot->metrics['days_since_last_revision'] ?? null;
        if ($snapshot->memorization_sessions > 0 && ($daysSinceRevision === null || $daysSinceRevision > $revisionDelay)) {
            $reason = $daysSinceRevision === null ? 'لا توجد جلسة مراجعة مسجلة للطالب.' : "مرّ {$daysSinceRevision} يومًا منذ آخر مراجعة.";
            $alerts[] = $this->raise($student, 'revision_delay', AlertSeverity::Warning, $reason, ['days_since_revision' => $daysSinceRevision, 'threshold' => $revisionDelay]);
        }

        $missingDays = (int) $this->settings->get('missing_record_days', config('system.academic.missing_record_days'), 'academic');
        $lastRecordDate = $student->dailyRecords()->max('record_date');
        $daysSinceRecord = $lastRecordDate ? now()->startOfDay()->diffInDays($lastRecordDate) : now()->startOfDay()->diffInDays($student->registration_date);
        if ($student->status->value === 'active' && $daysSinceRecord > $missingDays) {
            $alerts[] = $this->raise($student, 'missing_records', AlertSeverity::Warning, "لا يوجد سجل يومي للطالب منذ {$daysSinceRecord} يومًا.", ['days_since_record' => $daysSinceRecord, 'threshold' => $missingDays]);
        }

        return $alerts;
    }

    public function acknowledge(StudentAlert $alert, User $actor): StudentAlert
    {
        $alert->update([
            'status' => AlertStatus::Acknowledged->value,
            'acknowledged_at' => now(),
            'acknowledged_by' => $actor->id,
        ]);
        $this->auditLogger->record('student-alert.acknowledged', $alert, newValues: ['status' => AlertStatus::Acknowledged->value]);

        return $alert->refresh();
    }

    public function resolve(StudentAlert $alert, User $actor, string $notes): StudentAlert
    {
        $alert->update([
            'status' => AlertStatus::Resolved->value,
            'resolved_at' => now(),
            'resolved_by' => $actor->id,
            'resolution_notes' => $notes,
        ]);
        $this->auditLogger->record('student-alert.resolved', $alert, newValues: ['status' => AlertStatus::Resolved->value, 'resolution_notes' => $notes]);

        return $alert->refresh();
    }

    private function raise(
        Student $student,
        string $type,
        AlertSeverity $severity,
        string $reason,
        array $evidence,
        ?string $fingerprintScope = null,
        ?Halaqa $halaqaContext = null,
        ?CarbonInterface $recipientDate = null,
        bool $notifyAssignedStaff = false,
        bool $requireIncreasedAbsenceCountForRecurrence = false,
    ): StudentAlert {
        $fingerprintSource = "{$student->id}:{$type}";
        if ($fingerprintScope !== null) {
            $fingerprintSource .= ":{$fingerprintScope}";
        }
        $fingerprint = hash('sha256', $fingerprintSource);
        $alert = StudentAlert::query()->firstOrNew(['fingerprint' => $fingerprint]);
        $isNew = ! $alert->exists;
        $wasClosed = $alert->exists && in_array($alert->status?->value, [AlertStatus::Resolved->value, AlertStatus::Dismissed->value], true);
        $isRecurrence = $wasClosed && (
            ! $requireIncreasedAbsenceCountForRecurrence
            || (int) ($evidence['absence_count'] ?? 0) > (int) ($alert->evidence['absence_count'] ?? 0)
        );
        if ($wasClosed && ! $isRecurrence) {
            return $alert;
        }
        $halaqa = $halaqaContext ?? $student->currentHalaqa;
        $alert->fill([
            'student_id' => $student->id,
            'teacher_profile_id' => $this->activePrimaryTeacherId($halaqa, $recipientDate),
            'halaqa_id' => $halaqa?->id,
            'type' => $type,
            'severity' => $severity->value,
            'reason' => $reason,
            'status' => AlertStatus::Open->value,
            'evidence' => $evidence,
            'occurrence_count' => $isNew ? 1 : ($isRecurrence ? $alert->occurrence_count + 1 : $alert->occurrence_count),
            'generated_at' => now(),
            'acknowledged_at' => null,
            'acknowledged_by' => null,
            'resolved_at' => null,
            'resolved_by' => null,
            'resolution_notes' => null,
        ])->save();

        if ($severity === AlertSeverity::Critical && ($isNew || $isRecurrence)) {
            $this->notifyCriticalAlert($student, $alert);
        } elseif ($notifyAssignedStaff && $halaqa && $recipientDate && ($isNew || $isRecurrence)) {
            $this->notifyAssignedStaff($student, $alert, $halaqa, $recipientDate);
        }

        return $alert->refresh();
    }

    private function notifyCriticalAlert(Student $student, StudentAlert $alert): void
    {
        User::permission('alerts.view')
            ->where('active', true)
            ->get()
            ->filter(fn (User $user): bool => $this->visibility->canView($user, $student))
            ->each(function (User $user) use ($student, $alert): void {
                try {
                    $user->notify(new SystemNotification(
                        'تنبيه طلابي حرج',
                        "يتطلب تنبيه الطالب {$student->full_name} متابعة عاجلة: {$alert->reason}",
                        route('alerts.index'),
                        'error',
                    ));
                } catch (Throwable $exception) {
                    Log::warning('Critical alert notification failed.', [
                        'alert_id' => $alert->id,
                        'user_id' => $user->id,
                        'exception_class' => $exception::class,
                    ]);
                }
            });
    }

    /** @return array<int, StudentAlert> */
    private function monthlyAttendanceAlerts(Student $student, CarbonInterface $asOf): array
    {
        $periodStart = $asOf->copy()->startOfMonth();
        $periodEnd = $asOf->copy()->endOfDay();
        $periodKey = $periodStart->format('Y-m');
        $counts = $student->attendances()
            ->whereDate('record_date', '>=', $periodStart)
            ->whereDate('record_date', '<=', $periodEnd)
            ->whereIn('status', ['absent', 'excused'])
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');
        $unexcusedCount = (int) ($counts['absent'] ?? 0);
        $excusedCount = (int) ($counts['excused'] ?? 0);
        $allowance = max(0, (int) $this->settings->get(
            'monthly_excused_absence_allowance',
            config('system.academic.monthly_excused_absence_allowance', 3),
            'academic',
        ));
        $halaqa = $this->halaqaAt($student, $asOf);
        $alerts = [];

        if ($unexcusedCount >= 1) {
            $alerts[] = $this->raise(
                $student,
                'unexcused_absence',
                AlertSeverity::Warning,
                "سُجل للطالب غياب دون عذر خلال شهر {$periodStart->translatedFormat('F Y')}، ويحتاج ولي الأمر إلى متابعة.",
                [
                    'absence_count' => $unexcusedCount,
                    'allowance' => 0,
                    'period_from' => $periodStart->toDateString(),
                    'period_to' => $periodEnd->toDateString(),
                ],
                fingerprintScope: $periodKey,
                halaqaContext: $halaqa,
                recipientDate: $asOf,
                notifyAssignedStaff: true,
                requireIncreasedAbsenceCountForRecurrence: true,
            );
        }

        if ($excusedCount > $allowance) {
            $alerts[] = $this->raise(
                $student,
                'excessive_excused_absence',
                AlertSeverity::Warning,
                "تجاوز الطالب حد الغياب بعذر المسموح به شهريًا ({$allowance} أيام)، وسُجل له {$excusedCount} أيام.",
                [
                    'absence_count' => $excusedCount,
                    'allowance' => $allowance,
                    'period_from' => $periodStart->toDateString(),
                    'period_to' => $periodEnd->toDateString(),
                ],
                fingerprintScope: $periodKey,
                halaqaContext: $halaqa,
                recipientDate: $asOf,
                notifyAssignedStaff: true,
                requireIncreasedAbsenceCountForRecurrence: true,
            );
        }

        return $alerts;
    }

    private function halaqaAt(Student $student, CarbonInterface $asOf): ?Halaqa
    {
        $halaqaId = $student->attendances()
            ->whereDate('record_date', $asOf)
            ->value('halaqa_id');

        $halaqaId ??= $student->enrollments()
            ->whereDate('starts_at', '<=', $asOf)
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhereDate('ends_at', '>=', $asOf))
            ->latest('starts_at')
            ->value('halaqa_id');

        if ($halaqaId) {
            return Halaqa::query()->find($halaqaId);
        }

        return $student->currentHalaqa;
    }

    private function activePrimaryTeacherId(?Halaqa $halaqa, ?CarbonInterface $asOf): ?int
    {
        if (! $halaqa) {
            return null;
        }

        if (! $asOf) {
            return $halaqa->primary_teacher_id;
        }

        $activeAssignments = $halaqa->teacherAssignments()
            ->whereDate('starts_at', '<=', $asOf)
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhereDate('ends_at', '>=', $asOf))
            ->whereHas('teacher', fn ($teacher) => $teacher
                ->where('active', true)
                ->whereHas('user', fn ($user) => $user
                    ->where('active', true)
                    ->whereNull('archived_at')));

        return (clone $activeAssignments)
            ->where('role', 'primary')
            ->value('teacher_profile_id')
            ?? (clone $activeAssignments)->value('teacher_profile_id');
    }

    private function notifyAssignedStaff(
        Student $student,
        StudentAlert $alert,
        Halaqa $halaqa,
        CarbonInterface $asOf,
    ): void {
        $teacherUsers = User::query()
            ->role('teacher')
            ->permission('alerts.view')
            ->where('active', true)
            ->whereNull('archived_at')
            ->whereHas('teacherProfile', fn ($teacher) => $teacher
                ->where('active', true)
                ->whereHas('assignments', fn ($assignment) => $assignment
                    ->where('halaqa_id', $halaqa->id)
                    ->whereDate('starts_at', '<=', $asOf)
                    ->where(fn ($dates) => $dates->whereNull('ends_at')->orWhereDate('ends_at', '>=', $asOf))))
            ->get();
        $managerUsers = User::query()
            ->role('center-manager')
            ->permission('alerts.view')
            ->where('active', true)
            ->whereNull('archived_at')
            ->whereHas('staffProfile', fn ($staff) => $staff
                ->where('center_id', $halaqa->center_id)
                ->where('active', true))
            ->get();

        $teacherUsers
            ->concat($managerUsers)
            ->unique(fn (User $user): int => (int) $user->id)
            ->each(function (User $user) use ($student, $alert): void {
                try {
                    $url = $user->hasRole('teacher') && ! $user->hasRole('center-manager')
                        ? route('alerts.index', ['scope' => 'teaching'])
                        : route('alerts.index');
                    $title = $alert->type === 'unexcused_absence'
                        ? 'تنبيه غياب دون عذر'
                        : 'تجاوز الغياب بعذر';
                    $user->notify(new SystemNotification(
                        $title,
                        "الطالب {$student->full_name}: {$alert->reason}",
                        $url,
                        'warning',
                    ));
                } catch (Throwable $exception) {
                    Log::warning('Attendance alert notification failed.', [
                        'alert_id' => $alert->id,
                        'user_id' => $user->id,
                        'exception_class' => $exception::class,
                    ]);
                }
            });
    }
}
