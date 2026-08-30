<?php

namespace App\Services;

use App\Enums\RecitationType;
use App\Models\QuranAyah;
use App\Models\RecitationItem;
use App\Models\Student;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MemorizationJourneyService
{
    private const TOTAL_AYAHS = 6236;

    private const TOTAL_JUZ = 30;

    private ?Collection $cachedJuzRanges = null;

    /**
     * Calculate the center's sequential memorization journey from An-Nas toward Al-Fatiha.
     *
     * The earliest Quran position reached in a new-memorization record is the journey
     * frontier. A Juz is only counted as complete after its first Ayah is reached.
     */
    public function calculate(Student $student, CarbonInterface|string|null $asOf = null): array
    {
        $date = Carbon::parse($asOf ?? today())->endOfDay();
        $memorizedAyahIds = RecitationItem::query()
            ->select('start_ayah_id')
            ->where('type', RecitationType::NewMemorization->value)
            ->whereHas('dailyRecord', fn ($query) => $query
                ->where('student_id', $student->id)
                ->whereDate('record_date', '<=', $date));

        $frontier = QuranAyah::query()
            ->whereIn('id', $memorizedAyahIds)
            ->with('surah:id,name_arabic')
            ->orderBy('global_order')
            ->first(['id', 'surah_id', 'ayah_number', 'global_order', 'juz']);

        $lastRecordedAt = RecitationItem::query()
            ->where('type', RecitationType::NewMemorization->value)
            ->join('daily_records', 'daily_records.id', '=', 'recitation_items.daily_record_id')
            ->where('daily_records.student_id', $student->id)
            ->whereDate('daily_records.record_date', '<=', $date)
            ->max('daily_records.record_date');

        return $this->summarize($frontier, $lastRecordedAt);
    }

    /**
     * Build journey metrics for a preloaded frontier. This avoids per-student queries
     * when producing center-wide reports.
     */
    public function summarize(?QuranAyah $frontier, ?string $lastRecordedAt = null): array
    {
        if (! $frontier) {
            return $this->emptyJourney();
        }

        $juzRanges = $this->juzRanges();
        $currentJuzRange = $juzRanges->get((int) $frontier->juz);
        $completedJuz = $juzRanges
            ->filter(fn ($range) => (int) $range->range_start >= (int) $frontier->global_order)
            ->count();
        $completedJuz = min(self::TOTAL_JUZ, $completedJuz);

        $currentJuzProgress = 0.0;
        if ($currentJuzRange) {
            $juzAyahs = (int) $currentJuzRange->range_end - (int) $currentJuzRange->range_start + 1;
            $reachedAyahs = (int) $currentJuzRange->range_end - (int) $frontier->global_order + 1;
            $currentJuzProgress = round(min(100, ($reachedAyahs / max(1, $juzAyahs)) * 100), 1);
        }

        [$encouragementTitle, $encouragementMessage] = $this->encouragement($completedJuz);

        return [
            'has_progress' => true,
            'direction' => 'nas_to_fatiha',
            'completed_juz' => $completedJuz,
            'remaining_juz' => self::TOTAL_JUZ - $completedJuz,
            'next_juz' => $completedJuz < self::TOTAL_JUZ ? self::TOTAL_JUZ - $completedJuz : null,
            'completed_percentage' => round(($completedJuz / self::TOTAL_JUZ) * 100, 1),
            'journey_percentage' => round(((self::TOTAL_AYAHS - (int) $frontier->global_order + 1) / self::TOTAL_AYAHS) * 100, 1),
            'current_juz' => (int) $frontier->juz,
            'current_juz_progress' => $currentJuzProgress,
            'frontier_ayah_id' => (int) $frontier->id,
            'frontier_surah_id' => (int) $frontier->surah_id,
            'frontier_surah_name' => $frontier->surah->name_arabic,
            'frontier_ayah_number' => (int) $frontier->ayah_number,
            'last_recorded_at' => $lastRecordedAt,
            'encouragement_title' => $encouragementTitle,
            'encouragement_message' => $encouragementMessage,
        ];
    }

    private function juzRanges(): Collection
    {
        return $this->cachedJuzRanges ??= QuranAyah::query()
            ->select('juz', DB::raw('MIN(global_order) as range_start'), DB::raw('MAX(global_order) as range_end'))
            ->groupBy('juz')
            ->get()
            ->keyBy(fn ($range) => (int) $range->juz);
    }

    private function emptyJourney(): array
    {
        return [
            'has_progress' => false,
            'direction' => 'nas_to_fatiha',
            'completed_juz' => 0,
            'remaining_juz' => self::TOTAL_JUZ,
            'next_juz' => self::TOTAL_JUZ,
            'completed_percentage' => 0.0,
            'journey_percentage' => 0.0,
            'current_juz' => null,
            'current_juz_progress' => 0.0,
            'frontier_ayah_id' => null,
            'frontier_surah_id' => null,
            'frontier_surah_name' => null,
            'frontier_ayah_number' => null,
            'last_recorded_at' => null,
            'encouragement_title' => 'بداية مباركة',
            'encouragement_message' => 'بانتظار أول حفظ جديد؛ كل آية خطوة نور في هذه الرحلة.',
        ];
    }

    /** @return array{string, string} */
    private function encouragement(int $completedJuz): array
    {
        return match (true) {
            $completedJuz >= 30 => ['هنيئًا لك ختم الحفظ', 'ثلاثون جزءًا من كتاب الله؛ إنجاز عظيم يستحق الفخر والدعاء بالثبات.'],
            $completedJuz >= 25 => ['أوشكت على بلوغ القمة', 'خمسة أجزاء أو أقل تفصلك عن إتمام الرحلة؛ واصل بهذا الثبات الجميل.'],
            $completedJuz >= 20 => ['ثبات يثمر إنجازًا عظيمًا', 'قطعت ثلثي الطريق، وما بقي أقرب بإذن الله مع المراجعة والإتقان.'],
            $completedJuz >= 15 => ['بلغت منتصف الطريق', 'خمسة عشر جزءًا مباركًا؛ استمر فالهمة الصادقة تصنع الختمة.'],
            $completedJuz >= 10 => ['عشرة أجزاء من نور', 'إنجاز راسخ وخطوة كبيرة في رحلة القرآن؛ بارك الله في حفظك.'],
            $completedJuz >= 5 => ['خمسة أجزاء مباركة', 'ما شاء الله، تقدم واضح وجميل؛ واصل بنفس العزيمة والإتقان.'],
            $completedJuz >= 3 => ['ثلاثة أجزاء بإتقان', 'أحسنت، قطعت محطة مميزة في رحلتك من الناس إلى الفاتحة.'],
            $completedJuz >= 1 => ['أول جزء مكتمل', 'بداية قوية ومباركة؛ استمر فكل يوم يقربك من هدفك.'],
            default => ['خطواتك الأولى مباركة', 'أنت الآن تبني الجزء الأول؛ قليل دائم خير من كثير منقطع.'],
        };
    }
}
