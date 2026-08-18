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

    $cards = app(FindCardsToStudy::class)->handle($this->token->id, null);

    expect($cards->pluck('id')->all())->toBe([$highMiss->id, $lowMiss->id]);
});

test('never-studied cards come before studied ones once misses are tied', function () {
    $studied = Card::factory()->create(['deck_id' => $this->deck->id, 'aced_count' => 2, 'missed_count' => 0, 'last_reviewed_at' => now()]);
    $fresh = Card::factory()->create(['deck_id' => $this->deck->id, 'aced_count' => 0, 'missed_count' => 0, 'last_reviewed_at' => null]);

    $cards = app(FindCardsToStudy::class)->handle($this->token->id, null);

    expect($cards->pluck('id')->all())->toBe([$fresh->id, $studied->id]);
});

test('among equally-new cards, the least reviewed overall comes first', function () {
    $moreReviewed = Card::factory()->create(['deck_id' => $this->deck->id, 'aced_count' => 5, 'missed_count' => 0, 'last_reviewed_at' => now()]);
    $lessReviewed = Card::factory()->create(['deck_id' => $this->deck->id, 'aced_count' => 1, 'missed_count' => 0, 'last_reviewed_at' => now()]);

    $cards = app(FindCardsToStudy::class)->handle($this->token->id, null);

    expect($cards->pluck('id')->all())->toBe([$lessReviewed->id, $moreReviewed->id]);
});

test('it scopes to the given deck and to the token', function () {
    $otherDeck = Deck::factory()->create(['access_token_id' => $this->token->id]);
    $otherToken = AccessToken::factory()->create();
    $otherTokenDeck = Deck::factory()->create(['access_token_id' => $otherToken->id]);

    $inDeck = Card::factory()->create(['deck_id' => $this->deck->id]);
    Card::factory()->create(['deck_id' => $otherDeck->id]);
    Card::factory()->create(['deck_id' => $otherTokenDeck->id]);

    $cards = app(FindCardsToStudy::class)->handle($this->token->id, $this->deck);

    expect($cards->pluck('id')->all())->toBe([$inDeck->id]);
});

test('it caps the queue at the given limit', function () {
    Card::factory()->count(3)->create(['deck_id' => $this->deck->id]);

    $cards = app(FindCardsToStudy::class)->handle($this->token->id, null, limit: 2);

    expect($cards)->toHaveCount(2);
});
