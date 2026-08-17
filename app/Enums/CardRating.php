<?php

namespace App\Enums;

enum CardRating: string
{
    case Again = 'again';
    case Hard = 'hard';
    case Good = 'good';
    case Easy = 'easy';

    public function easeDelta(): float
    {
        return match ($this) {
            self::Again => -0.20,
            self::Hard => -0.15,
            self::Good => 0.0,
            self::Easy => 0.15,
        };
    }
}
