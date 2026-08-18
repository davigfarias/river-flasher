<?php

use App\Actions\CreateDeck;
use App\DTO\DeckData;
use App\Models\AccessToken;
use App\Models\Deck;

test('it creates a deck with a slug derived from the name', function () {
    $token = AccessToken::factory()->create();

    $deck = app(CreateDeck::class)->handle($token, new DeckData(
        name: 'Koine Greek — New Testament Vocab',
    ));

    expect($deck->access_token_id)->toBe($token->id)
        ->and($deck->slug)->toBe('koine-greek-new-testament-vocab');
});

test('it disambiguates a slug collision within the same token', function () {
    $token = AccessToken::factory()->create();
    Deck::factory()->create(['access_token_id' => $token->id, 'slug' => 'greek-verbs']);

    $deck = app(CreateDeck::class)->handle($token, new DeckData(
        name: 'Greek Verbs',
    ));

    expect($deck->slug)->toBe('greek-verbs-2');
});

test('the same deck name is allowed across two different tokens', function () {
    $tokenA = AccessToken::factory()->create();
    $tokenB = AccessToken::factory()->create();

    $data = new DeckData(name: 'Greek Verbs');

    $deckA = app(CreateDeck::class)->handle($tokenA, $data);
    $deckB = app(CreateDeck::class)->handle($tokenB, $data);

    expect($deckA->slug)->toBe('greek-verbs')
        ->and($deckB->slug)->toBe('greek-verbs');
});
