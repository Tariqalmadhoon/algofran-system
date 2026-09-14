<?php

namespace App\Enums;

enum AttendanceStatus: string
{
    case Present = 'present';
    case Absent = 'absent';
    case Excused = 'excused';
    case Late = 'late';

    public function allowsRecitation(): bool
    {
        return match ($this) {
            self::Present, self::Late => true,
            self::Absent, self::Excused => false,
        };
    }

    public function isAbsence(): bool
    {
        return ! $this->allowsRecitation();
    }

    public function label(): string
    {
        return match ($this) {
            self::Present => 'حاضر',
            self::Absent => 'غائب',
            self::Excused => 'بعذر',
            self::Late => 'متأخر',
        };
    }
}
