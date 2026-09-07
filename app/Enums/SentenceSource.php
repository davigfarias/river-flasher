<?php

namespace App\Enums;

enum SentenceSource: string
{
    case Manual = 'manual';
    case Ai = 'ai';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'manual',
            self::Ai => 'IA',
        };
    }
}
