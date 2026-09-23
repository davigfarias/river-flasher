<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\GrammaticalNumber;
use App\Enums\GrammaticalPerson;
use App\Support\Grammar\Paradigm;

/**
 * Builds one inflected verb form by concatenating `stem . ending`, the same
 * way GenerateInflectedForm does for nouns — see its docblock for the
 * documented accent limitation, which applies here too.
 */
final readonly class GenerateInflectedVerbForm
{
    public function handle(
        string $stem,
        Paradigm $paradigm,
        GrammaticalPerson $person,
        GrammaticalNumber $number,
    ): string {
        return $stem.($paradigm->endingForPerson($person, $number) ?? '');
    }
}
