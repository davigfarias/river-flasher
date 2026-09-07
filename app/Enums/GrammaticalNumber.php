<?php

namespace App\Enums;

enum GrammaticalNumber: string
{
    case Singular = 'sg';
    case Plural = 'pl';

    public function label(): string
    {
        return match ($this) {
            self::Singular => 'singular',
            self::Plural => 'plural',
        };
    }
}
