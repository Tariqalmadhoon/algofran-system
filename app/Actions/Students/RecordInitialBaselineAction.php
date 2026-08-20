<?php

namespace App\Actions\Students;

use App\Models\Student;
use App\Models\StudentMemorizationBaseline;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\QuranRangeService;
use App\Services\StudentAchievementEngine;
use App\Services\StudentProgressService;
use App\Services\StudentTimelineService;

class RecordInitialBaselineAction
{
    public function __construct(
        private readonly QuranRangeService $quranRange,
        private readonly StudentTimelineService $timeline,
        private readonly AuditLogger $auditLogger,
        private readonly StudentProgressService $progress,
        private readonly StudentAchievementEngine $achievements,
    ) {}

    public function execute(Student $student, int $startAyahId, int $endAyahId, string $recordedAt, User $actor, ?string $notes = null): StudentMemorizationBaseline
    {
        [$start, $end] = $this->quranRange->validate($startAyahId, $endAyahId);
        $baseline = $student->baselines()->create([
            'start_ayah_id' => $start->id,
            'end_ayah_id' => $end->id,
            'recorded_at' => $recordedAt,
            'recorded_by' => $actor->id,
            'notes' => $notes,
        ]);
        $baseline->load(['startAyah.surah', 'endAyah.surah']);
        $description = "من {$baseline->startAyah->surah->name_arabic} {$start->ayah_number} إلى {$baseline->endAyah->surah->name_arabic} {$end->ayah_number}";
        $this->timeline->record($student, 'baseline.recorded', 'تسجيل المحفوظ السابق', $baseline, $description, occurredAt: $recordedAt);
        $this->auditLogger->record('student.baseline.created', $baseline, newValues: $baseline->getAttributes());
        $snapshot = $this->progress->snapshot($student);
        $this->achievements->evaluate($student, $snapshot);

        return $baseline;
    }
}
