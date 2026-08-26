<?php

use App\Actions\GetAvailableCategories;
use App\Enums\Language;
use App\Models\AccessToken;
use App\Models\Card;
use App\Models\Deck;

beforeEach(function () {
    $this->token = AccessToken::factory()->create();
    $this->deck = Deck::factory()->create(['access_token_id' => $this->token->id]);
});

test('it returns distinct categories for the given language, scoped to the token', function () {
    Card::factory()->create(['deck_id' => $this->deck->id, 'language' => Language::Greek, 'category' => 'Partes do corpo']);
    Card::factory()->create(['deck_id' => $this->deck->id, 'language' => Language::Greek, 'category' => 'Partes do corpo']);
    Card::factory()->create(['deck_id' => $this->deck->id, 'language' => Language::Greek, 'category' => 'Objetos de casa']);
    Card::factory()->create(['deck_id' => $this->deck->id, 'language' => Language::Hebrew, 'category' => 'Partes do corpo']);

    $otherToken = AccessToken::factory()->create();
    $otherDeck = Deck::factory()->create(['access_token_id' => $otherToken->id]);
    Card::factory()->create(['deck_id' => $otherDeck->id, 'language' => Language::Greek, 'category' => 'Animais']);

    $categories = app(GetAvailableCategories::class)->handle($this->token->id, Language::Greek);

    expect($categories->all())->toBe(['Objetos de casa', 'Partes do corpo']);
});

test('it ignores cards without a category', function () {
    Card::factory()->create(['deck_id' => $this->deck->id, 'language' => Language::Greek, 'category' => null]);

    $categories = app(GetAvailableCategories::class)->handle($this->token->id, Language::Greek);

    expect($categories->all())->toBe([]);
});
