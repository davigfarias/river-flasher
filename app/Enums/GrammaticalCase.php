<?php

namespace App\Enums;

enum GrammaticalCase: string
{
    case Nominative = 'nom';
    case Genitive = 'gen';
    case Dative = 'dat';
    case Accusative = 'acc';
    case Vocative = 'voc';

    public function label(): string
    {
        return match ($this) {
            self::Nominative => 'nominativo',
            self::Genitive => 'genitivo',
            self::Dative => 'dativo',
            self::Accusative => 'acusativo',
            self::Vocative => 'vocativo',
        };
    }
}
