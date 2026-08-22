<?php

use App\Actions\GetAvailableTags;
use App\Enums\Language;
use App\Models\AccessToken;
use App\Models\Card;
use App\Models\Deck;

beforeEach(function () {
    $this->token = AccessToken::factory()->create();
    $this->deck = Deck::factory()->create(['access_token_id' => $this->token->id]);
});

test('it returns distinct tags for the given language, scoped to the token', function () {
    Card::factory()->create(['deck_id' => $this->deck->id, 'language' => Language::Greek, 'pos' => 'Preposição']);
    Card::factory()->create(['deck_id' => $this->deck->id, 'language' => Language::Greek, 'pos' => 'Preposição']);
    Card::factory()->create(['deck_id' => $this->deck->id, 'language' => Language::Greek, 'pos' => 'Verbo']);
    Card::factory()->create(['deck_id' => $this->deck->id, 'language' => Language::Hebrew, 'pos' => 'Preposição']);

    $otherToken = AccessToken::factory()->create();
    $otherDeck = Deck::factory()->create(['access_token_id' => $otherToken->id]);
    Card::factory()->create(['deck_id' => $otherDeck->id, 'language' => Language::Greek, 'pos' => 'Advérbio']);

    $tags = app(GetAvailableTags::class)->handle($this->token->id, Language::Greek);

    expect($tags->all())->toBe(['Preposição', 'Verbo']);
});

test('it ignores cards without a tag', function () {
    Card::factory()->create(['deck_id' => $this->deck->id, 'language' => Language::Greek, 'pos' => null]);

    $tags = app(GetAvailableTags::class)->handle($this->token->id, Language::Greek);

    expect($tags->all())->toBe([]);
});
