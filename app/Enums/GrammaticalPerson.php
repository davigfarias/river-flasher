<?php

namespace App\Enums;

enum GrammaticalPerson: string
{
    case First = '1';
    case Second = '2';
    case Third = '3';

    public function label(): string
    {
        return match ($this) {
            self::First => 'primeira pessoa',
            self::Second => 'segunda pessoa',
            self::Third => 'terceira pessoa',
        };
    }
}
