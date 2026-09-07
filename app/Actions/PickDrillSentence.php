<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Sentence;

final readonly class PickDrillSentence
{
    /**
     * A random approved sentence of the deck that has at least one
     * blank-able token: inflected (case + number) and linked to a
     * declinable card, so distractors can be generated from it.
     */
    public function handle(int $deckId, ?string $grammarFocus = null): ?Sentence
    {
        return Sentence::query()
            ->approved()
            ->where('deck_id', $deckId)
            ->when($grammarFocus !== null, fn ($query) => $query->where('grammar_focus', $grammarFocus))
            ->whereHas('tokens', fn ($query) => $query
                ->where('is_target', true)
                ->whereNotNull('card_id')
                ->whereNotNull('grammatical_case')
                ->whereNotNull('grammatical_number')
            )
            ->whereHas('tokens.card', fn ($query) => $query->declinable())
            ->with(['tokens' => fn ($query) => $query->orderBy('position'), 'tokens.card'])
            ->inRandomOrder()
            ->first();
    }
}
