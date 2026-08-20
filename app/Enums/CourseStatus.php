<?php

namespace App\Enums;

enum CourseStatus: string
{
    case Planned = 'planned';
    case Active = 'active';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Planned => 'مخططة',
            self::Active => 'نشطة',
            self::Completed => 'مكتملة',
            self::Cancelled => 'ملغاة',
        };
    }
}
