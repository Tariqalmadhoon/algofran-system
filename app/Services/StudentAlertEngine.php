<?php

namespace App\Services;

use App\Enums\AlertSeverity;
use App\Enums\AlertStatus;
use App\Enums\EvaluationRating;
use App\Models\RecitationItem;
use App\Models\Student;
use App\Models\StudentAlert;
use App\Models\StudentProgressSnapshot;
use App\Models\User;
use App\Notifications\SystemNotification;
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
    public function evaluate(Student $student, StudentProgressSnapshot $snapshot): array
    {
        $alerts = [];
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

        $absenceThreshold = (int) $this->settings->get('absence_alert_count', config('system.academic.absence_alert_count'), 'academic');
        $absenceCount = $student->attendances()->where('status', 'absent')->whereDate('record_date', '>=', today()->subDays(29))->count();
        if ($absenceCount >= $absenceThreshold) {
            $alerts[] = $this->raise($student, 'frequent_absence', $absenceCount >= $absenceThreshold + 2 ? AlertSeverity::Critical : AlertSeverity::Warning, "سُجل غياب الطالب {$absenceCount} مرات خلال آخر 30 يومًا.", ['absence_count' => $absenceCount, 'period_days' => 30]);
        }

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

    private function raise(Student $student, string $type, AlertSeverity $severity, string $reason, array $evidence): StudentAlert
    {
        $fingerprint = hash('sha256', "{$student->id}:{$type}");
        $alert = StudentAlert::query()->firstOrNew(['fingerprint' => $fingerprint]);
        $isNew = ! $alert->exists;
        $isRecurrence = $alert->exists && in_array($alert->status?->value, [AlertStatus::Resolved->value, AlertStatus::Dismissed->value], true);
        $alert->fill([
            'student_id' => $student->id,
            'teacher_profile_id' => $student->currentHalaqa?->primary_teacher_id,
            'halaqa_id' => $student->current_halaqa_id,
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
}
