<?php

namespace App\Enums;

enum ReviewResult: string
{
    case Remembered = 'remembered';
    case Forgot = 'forgot';

    public function remembered(): bool
    {
        return $this === self::Remembered;
    }
}
