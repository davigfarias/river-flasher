<?php

use App\Actions\CreateDeck;
use App\DTO\DeckData;
use App\Models\AccessToken;

test('it creates a deck with an auto-generated uuid', function () {
    $token = AccessToken::factory()->create();

    $deck = app(CreateDeck::class)->handle($token, new DeckData(
        name: 'Koine Greek — New Testament Vocab',
    ));

    expect($deck->access_token_id)->toBe($token->id)
        ->and($deck->uuid)->toBeUuid();
});

test('the same deck name is allowed across two different tokens', function () {
    $tokenA = AccessToken::factory()->create();
    $tokenB = AccessToken::factory()->create();

    $data = new DeckData(name: 'Greek Verbs');

    $deckA = app(CreateDeck::class)->handle($tokenA, $data);
    $deckB = app(CreateDeck::class)->handle($tokenB, $data);

    expect($deckA->uuid)->not->toBe($deckB->uuid);
});
