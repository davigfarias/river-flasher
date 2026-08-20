<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTO\DeckData;
use App\Models\AccessToken;
use App\Models\Deck;

final readonly class CreateDeck
{
    public function handle(AccessToken $token, DeckData $data): Deck
    {
        return Deck::create([
            'access_token_id' => $token->id,
            'name' => $data->name,
        ]);
    }
}
