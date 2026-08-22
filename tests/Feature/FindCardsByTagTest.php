<?php

use App\Actions\FindCardsByTag;
use App\Enums\Language;
use App\Models\AccessToken;
use App\Models\Card;
use App\Models\Deck;

beforeEach(function () {
    $this->token = AccessToken::factory()->create();
    $this->deck = Deck::factory()->create(['access_token_id' => $this->token->id]);
});

test('it finds cards matching the tag and language, scoped to the token', function () {
    $match = Card::factory()->create(['deck_id' => $this->deck->id, 'language' => Language::Greek, 'pos' => 'Preposição']);
    Card::factory()->create(['deck_id' => $this->deck->id, 'language' => Language::Greek, 'pos' => 'Verbo']);
    Card::factory()->create(['deck_id' => $this->deck->id, 'language' => Language::Hebrew, 'pos' => 'Preposição']);

    $otherToken = AccessToken::factory()->create();
    $otherDeck = Deck::factory()->create(['access_token_id' => $otherToken->id]);
    Card::factory()->create(['deck_id' => $otherDeck->id, 'language' => Language::Greek, 'pos' => 'Preposição']);

    $cards = app(FindCardsByTag::class)->handle($this->token->id, Language::Greek, 'Preposição');

    expect($cards->pluck('id')->all())->toBe([$match->id]);
});
