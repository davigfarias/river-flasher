<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\GrammaticalCase;
use App\Enums\GrammaticalNumber;
use App\Support\Grammar\Paradigm;

/**
 * Builds one inflected form by concatenating `stem . ending`.
 *
 * Documented limitation: concatenation gets the letters right but does
 * not move the accent (ἄνθρωπος -> ἀνθρωπου, not ἀνθρώπου). The drills
 * are about endings, not accentuation, and GreekFormComparator ignores
 * diacritics — so this is deliberate, not a bug to fix here.
 */
final readonly class GenerateInflectedForm
{
    public function handle(
        string $stem,
        Paradigm $paradigm,
        GrammaticalCase $case,
        GrammaticalNumber $number,
        ?string $nomSgOverride = null,
    ): string {
        $ending = $paradigm->endingFor($case, $number);

        if ($ending === null) {
            // An unpredictable cell (e.g. 3rd-decl nom sg). Only the
            // nominative singular has an override; anything else stays empty
            // and the caller filters it out.
            return $case === GrammaticalCase::Nominative && $number === GrammaticalNumber::Singular
                ? (string) $nomSgOverride
                : '';
        }

        return $stem.$ending;
    }
}
