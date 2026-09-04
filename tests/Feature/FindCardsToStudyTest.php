<?php

use App\Actions\FindCardsToStudy;
use App\Models\AccessToken;
use App\Models\Card;
use App\Models\Deck;

beforeEach(function () {
    $this->token = AccessToken::factory()->create();
    $this->deck = Deck::factory()->create(['access_token_id' => $this->token->id]);
});

test('cards with more misses come first', function () {
    $lowMiss = Card::factory()->create(['deck_id' => $this->deck->id, 'aced_count' => 1, 'missed_count' => 1, 'last_reviewed_at' => now()]);
    $highMiss = Card::factory()->create(['deck_id' => $this->deck->id, 'aced_count' => 0, 'missed_count' => 5, 'last_reviewed_at' => now()]);

    $cards = app(FindCardsToStudy::class)->handle($this->token->id);

    expect($cards->pluck('id')->all())->toBe([$highMiss->id, $lowMiss->id]);
});

test('never-studied cards come before studied ones once misses are tied', function () {
    $studied = Card::factory()->create(['deck_id' => $this->deck->id, 'aced_count' => 2, 'missed_count' => 0, 'last_reviewed_at' => now()]);
    $fresh = Card::factory()->create(['deck_id' => $this->deck->id, 'aced_count' => 0, 'missed_count' => 0, 'last_reviewed_at' => null]);

    $cards = app(FindCardsToStudy::class)->handle($this->token->id);

    expect($cards->pluck('id')->all())->toBe([$fresh->id, $studied->id]);
});

test('among equally-new cards, the least reviewed overall comes first', function () {
    $moreReviewed = Card::factory()->create(['deck_id' => $this->deck->id, 'aced_count' => 5, 'missed_count' => 0, 'last_reviewed_at' => now()]);
    $lessReviewed = Card::factory()->create(['deck_id' => $this->deck->id, 'aced_count' => 1, 'missed_count' => 0, 'last_reviewed_at' => now()]);

    $cards = app(FindCardsToStudy::class)->handle($this->token->id);

    expect($cards->pluck('id')->all())->toBe([$lessReviewed->id, $moreReviewed->id]);
});

test('it scopes to the given deck and to the token', function () {
    $otherDeck = Deck::factory()->create(['access_token_id' => $this->token->id]);
    $otherToken = AccessToken::factory()->create();
    $otherTokenDeck = Deck::factory()->create(['access_token_id' => $otherToken->id]);

    $inDeck = Card::factory()->create(['deck_id' => $this->deck->id]);
    Card::factory()->create(['deck_id' => $otherDeck->id]);
    Card::factory()->create(['deck_id' => $otherTokenDeck->id]);

    $cards = app(FindCardsToStudy::class)->handle($this->token->id, [$this->deck->id]);

    expect($cards->pluck('id')->all())->toBe([$inDeck->id]);
});

test('it scopes to several given decks at once', function () {
    $otherDeck = Deck::factory()->create(['access_token_id' => $this->token->id]);
    $excludedDeck = Deck::factory()->create(['access_token_id' => $this->token->id]);

    $inFirst = Card::factory()->create(['deck_id' => $this->deck->id]);
    $inSecond = Card::factory()->create(['deck_id' => $otherDeck->id]);
    Card::factory()->create(['deck_id' => $excludedDeck->id]);

    $cards = app(FindCardsToStudy::class)->handle($this->token->id, [$this->deck->id, $otherDeck->id]);

    expect($cards->pluck('id')->sort()->values()->all())->toBe(collect([$inFirst->id, $inSecond->id])->sort()->values()->all());
});

test('it caps the queue at the given limit', function () {
    Card::factory()->count(3)->create(['deck_id' => $this->deck->id]);

    $cards = app(FindCardsToStudy::class)->handle($this->token->id, [], limit: 2);

    expect($cards)->toHaveCount(2);
});

test('deactivated cards are excluded, in both study-all and per-deck sessions', function () {
    $active = Card::factory()->create(['deck_id' => $this->deck->id]);
    Card::factory()->inactive()->create(['deck_id' => $this->deck->id]);

    $allDecks = app(FindCardsToStudy::class)->handle($this->token->id);
    $thisDeck = app(FindCardsToStudy::class)->handle($this->token->id, [$this->deck->id]);

    expect($allDecks->pluck('id')->all())->toBe([$active->id])
        ->and($thisDeck->pluck('id')->all())->toBe([$active->id]);
});
