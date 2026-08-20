<?php

use App\Actions\GetDeckLanguage;
use App\Enums\Language;
use App\Models\Card;
use App\Models\Deck;

test('an empty deck has no language', function () {
    $deck = Deck::factory()->create();

    expect(app(GetDeckLanguage::class)->handle($deck))->toBeNull();
});

test('a deck\'s language comes from its cards', function () {
    $deck = Deck::factory()->create();
    Card::factory()->create(['deck_id' => $deck->id, 'language' => Language::Hebrew]);

    expect(app(GetDeckLanguage::class)->handle($deck))->toBe(Language::Hebrew);
});

test('a mixed legacy deck resolves to its first card\'s language', function () {
    $deck = Deck::factory()->create();
    $first = Card::factory()->create(['deck_id' => $deck->id, 'language' => Language::Greek]);
    Card::factory()->create(['deck_id' => $deck->id, 'language' => Language::Hebrew]);

    expect($first->language)->toBe(Language::Greek)
        ->and(app(GetDeckLanguage::class)->handle($deck))->toBe(Language::Greek);
});
