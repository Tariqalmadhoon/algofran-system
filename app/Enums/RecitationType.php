<?php

namespace App\Enums;

enum RecitationType: string
{
    case NewMemorization = 'new_memorization';
    case RecentRevision = 'recent_revision';
    case OldRevision = 'old_revision';
    case Recitation = 'recitation';
    case Exam = 'exam';
    case Tajweed = 'tajweed';

    public function label(): string
    {
        return match ($this) {
            self::NewMemorization => 'حفظ جديد',
            self::RecentRevision => 'مراجعة قريبة',
            self::OldRevision => 'مراجعة قديمة',
            self::Recitation => 'تلاوة',
            self::Exam => 'اختبار',
            self::Tajweed => 'تجويد',
        };
    }
}
