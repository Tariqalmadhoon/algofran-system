<?php

namespace App\Enums;

enum EvaluationRating: string
{
    case Poor = 'poor';
    case Good = 'good';
    case VeryGood = 'very_good';
    case Excellent = 'excellent';

    public function label(): string
    {
        return match ($this) {
            self::Poor => 'ضعيف',
            self::Good => 'جيد',
            self::VeryGood => 'جيد جدًا',
            self::Excellent => 'ممتاز',
        };
    }

    public function score(): int
    {
        return match ($this) {
            self::Poor => 25,
            self::Good => 60,
            self::VeryGood => 80,
            self::Excellent => 100,
        };
    }
}
