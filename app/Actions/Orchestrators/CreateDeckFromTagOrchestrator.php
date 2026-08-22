<?php

declare(strict_types=1);

namespace App\Actions\Orchestrators;

use App\Actions\CreateDeck;
use App\Actions\MoveCardsToDeck;
use App\DTO\DeckData;
use App\Models\AccessToken;
use App\Models\Deck;
use Illuminate\Support\Facades\DB;

final readonly class CreateDeckFromTagOrchestrator
{
    public function __construct(
        private CreateDeck $createDeck,
        private MoveCardsToDeck $moveCardsToDeck,
    ) {}

    /**
     * Creates a new deck and moves the given (already ownership-verified)
     * cards into it. Cards belong to a single deck, so this pulls them out
     * of wherever they currently live.
     *
     * @param  array<int, int>  $cardIds
     */
    public function handle(AccessToken $token, DeckData $data, array $cardIds): Deck
    {
        return DB::transaction(function () use ($token, $data, $cardIds): Deck {
            $deck = $this->createDeck->handle($token, $data);

            $this->moveCardsToDeck->handle($deck, $cardIds);

            return $deck;
        });
    }
}
