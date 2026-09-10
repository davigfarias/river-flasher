<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Deck;
use App\Models\TranslationExercise;

/**
 * Clears every cached tradução exercise for a deck. Each card's exercise is
 * lazily rebuilt from its current `example` / `translation` the next time it
 * comes up in a session — this is how the user picks up edits made to a card
 * after it was first drilled.
 */
final readonly class ResetTranslationBank
{
    public function handle(Deck $deck): int
    {
        return TranslationExercise::query()
            ->whereIn('card_id', $deck->cards()->select('id'))
            ->delete();
    }
}
