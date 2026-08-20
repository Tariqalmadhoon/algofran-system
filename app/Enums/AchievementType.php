<?php

namespace App\Enums;

enum AchievementType: string
{
    case SurahCompletion = 'surah_completion';
    case JuzCompletion = 'juz_completion';
    case QuranMilestone = 'quran_milestone';
    case Competition = 'competition';
    case Award = 'award';
    case Course = 'course';
    case Manual = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::SurahCompletion => 'إكمال سورة',
            self::JuzCompletion => 'إكمال جزء',
            self::QuranMilestone => 'مرحلة قرآنية',
            self::Competition => 'مسابقة',
            self::Award => 'تكريم',
            self::Course => 'دورة',
            self::Manual => 'إنجاز يدوي',
        };
    }
}
