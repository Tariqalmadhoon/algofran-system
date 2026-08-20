<?php

namespace App\Enums;

enum AlertStatus: string
{
    case Open = 'open';
    case Acknowledged = 'acknowledged';
    case Resolved = 'resolved';
    case Dismissed = 'dismissed';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'مفتوح',
            self::Acknowledged => 'قيد المتابعة',
            self::Resolved => 'تمت المعالجة',
            self::Dismissed => 'مستبعد',
        };
    }
}
