<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Card;
use App\Models\Deck;

final readonly class MoveCardsToDeck
{
    /**
     * Reassigns the given cards to $deck. Ownership of the card ids must be
     * verified by the caller — this only moves whatever ids it's handed.
     *
     * @param  array<int, int>  $cardIds
     */
    public function handle(Deck $deck, array $cardIds): int
    {
        return Card::query()->whereIn('id', $cardIds)->update(['deck_id' => $deck->id]);
    }
}
