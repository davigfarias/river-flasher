<?php

namespace App\Enums;

enum Gender: string
{
    case Masculine = 'masc';
    case Feminine = 'fem';
    case Neuter = 'neut';

    public function label(): string
    {
        return match ($this) {
            self::Masculine => 'masculino',
            self::Feminine => 'feminino',
            self::Neuter => 'neutro',
        };
    }
}
