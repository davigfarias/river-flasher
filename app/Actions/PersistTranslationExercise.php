<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Card;
use App\Models\TranslationExercise;

/**
 * Upserts the cached tradução exercise for a card: its target sentence and the
 * ordered list of correct translation tokens. One row per card — calling this
 * again (e.g. after a bank reset) rebuilds it from the card's current fields.
 */
final readonly class PersistTranslationExercise
{
    /**
     * @param  array<int, string>  $correctTokens
     */
    public function handle(Card $card, array $correctTokens): TranslationExercise
    {
        return TranslationExercise::updateOrCreate(
            ['card_id' => $card->id],
            [
                'access_token_id' => $card->deck->access_token_id,
                'target_text' => (string) $card->example,
                'tokens' => array_values($correctTokens),
            ],
        );
    }
}
