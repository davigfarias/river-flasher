<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Card;
use App\Models\Deck;
use Illuminate\Database\Eloquent\Collection;

final readonly class FindDeckCardsForSentences
{
    /**
     * The vocabulary a generated sentence may draw on: active, declinable
     * (stem + paradigm) cards of the deck. Anything the engine can't
     * inflect can't be morphology-checked, so it stays out.
     *
     * @return Collection<int, Card>
     */
    public function handle(Deck $deck): Collection
    {
        return $deck->cards()->active()->declinable()->orderBy('word')->get();
    }
}
