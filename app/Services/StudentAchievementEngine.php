<?php

namespace App\Services;

use App\Enums\AchievementType;
use App\Models\Achievement;
use App\Models\Student;
use App\Models\StudentProgressSnapshot;

class StudentAchievementEngine
{
    public function __construct(
        private readonly StudentTimelineService $timeline,
        private readonly AuditLogger $auditLogger,
    ) {}

    /** @return array<int, Achievement> */
    public function evaluate(Student $student, StudentProgressSnapshot $snapshot): array
    {
        $milestones = [];
        if ($snapshot->completed_surahs >= 1) {
            $milestones[] = ['surah-first', AchievementType::SurahCompletion, 'إكمال أول سورة', 'أكمل الطالب سورة كاملة وفق نطاقات الحفظ المسجلة.'];
        }
        foreach ([1, 5, 10, 15, 30] as $juz) {
            if ($snapshot->completed_juz >= $juz) {
                $milestones[] = ["juz-{$juz}", AchievementType::JuzCompletion, "إكمال {$juz} جزء", "بلغ الطالب إكمال {$juz} جزء من القرآن الكريم."];
            }
        }
        if ($snapshot->memorized_ayahs >= 3118) {
            $milestones[] = ['quran-half', AchievementType::QuranMilestone, 'حفظ نصف القرآن', 'بلغ الطالب نصف عدد آيات القرآن الكريم وفق السجلات المعتمدة.'];
        }

        $created = [];
        foreach ($milestones as [$key, $type, $title, $description]) {
            $fingerprint = hash('sha256', "{$student->id}:{$key}");
            $achievement = Achievement::query()->firstOrCreate(
                ['fingerprint' => $fingerprint],
                [
                    'student_id' => $student->id,
                    'type' => $type->value,
                    'title' => $title,
                    'description' => $description,
                    'achieved_at' => $snapshot->as_of_date,
                    'issuer' => 'النظام الأكاديمي',
                    'metadata' => ['milestone' => $key, 'snapshot_id' => $snapshot->id],
                ],
            );
            if ($achievement->wasRecentlyCreated) {
                $this->timeline->record($student, 'achievement.generated', 'إنجاز قرآني', $achievement, $title, ['milestone' => $key], $snapshot->as_of_date);
                $this->auditLogger->record('achievement.generated', $achievement, newValues: $achievement->getAttributes());
                $created[] = $achievement;
            }
        }

        return $created;
    }
}
