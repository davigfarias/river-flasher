<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTO\DeckData;
use App\Models\Deck;

final readonly class UpdateDeck
{
    public function handle(Deck $deck, DeckData $data): Deck
    {
        $deck->update(['name' => $data->name]);

        return $deck;
    }
}
