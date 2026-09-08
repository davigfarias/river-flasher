<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Card;

final readonly class SetCardMorphologyExclusion
{
    /**
     * Mark a card as out of (or back into) the deck morphology annotation
     * list. Excluded cards are words the user has judged indeclinable, so
     * they shouldn't clutter the screen while the declinable ones are
     * being annotated.
     */
    public function handle(Card $card, bool $excluded): void
    {
        $card->update([
            'morphology_excluded_at' => $excluded ? now() : null,
        ]);
    }
}
