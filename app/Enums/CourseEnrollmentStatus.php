<?php

namespace App\Enums;

enum CourseEnrollmentStatus: string
{
    case Enrolled = 'enrolled';
    case Completed = 'completed';
    case Withdrawn = 'withdrawn';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Enrolled => 'ملتحق',
            self::Completed => 'مكتمل',
            self::Withdrawn => 'منسحب',
            self::Failed => 'غير مجتاز',
        };
    }
}
