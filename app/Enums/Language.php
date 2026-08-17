<?php

namespace App\Enums;

enum Language: string
{
    case Greek = 'el';
    case Hebrew = 'he';

    public function label(): string
    {
        return match ($this) {
            self::Greek => 'Greek',
            self::Hebrew => 'Hebrew',
        };
    }

    public function isRtl(): bool
    {
        return $this === self::Hebrew;
    }
}
