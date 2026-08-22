<?php

use App\Actions\Orchestrators\CreateDeckFromTagOrchestrator;
use App\DTO\DeckData;
use App\Models\AccessToken;
use App\Models\Card;
use App\Models\Deck;

test('it creates a deck and moves the given cards into it', function () {
    $token = AccessToken::factory()->create();
    $origin = Deck::factory()->create(['access_token_id' => $token->id]);
    $cards = Card::factory()->count(2)->create(['deck_id' => $origin->id]);

    $deck = app(CreateDeckFromTagOrchestrator::class)->handle(
        $token,
        new DeckData(name: 'Preposições'),
        $cards->pluck('id')->all(),
    );

    expect($deck->name)->toBe('Preposições')
        ->and($deck->access_token_id)->toBe($token->id)
        ->and($deck->cards()->pluck('id')->all())->toBe($cards->pluck('id')->all());
});
