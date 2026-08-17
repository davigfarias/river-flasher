<?php

use App\Actions\CountMasteredCards;
use App\Enums\CardRating;
use App\Models\AccessToken;
use App\Models\Card;
use App\Models\Deck;
use App\Models\Review;
use Carbon\CarbonImmutable;

beforeEach(function () {
    $this->token = AccessToken::factory()->create();
    $this->deck = Deck::factory()->create(['access_token_id' => $this->token->id]);
    $this->now = CarbonImmutable::parse('2026-08-17 12:00:00');
});

test('it totals every card past the mature interval threshold, scoped to the token', function () {
    Card::factory()->mature()->create(['deck_id' => $this->deck->id]);
    Card::factory()->mature()->create(['deck_id' => $this->deck->id]);
    Card::factory()->review()->create(['deck_id' => $this->deck->id]); // not mature yet

    $otherToken = AccessToken::factory()->create();
    $otherDeck = Deck::factory()->create(['access_token_id' => $otherToken->id]);
    Card::factory()->mature()->create(['deck_id' => $otherDeck->id]);

    $result = app(CountMasteredCards::class)->handle($this->token->id, $this->now);

    expect($result['total'])->toBe(2);
});

test('a card only counts as matured this week if its first mature review happened in the last 7 days', function () {
    $recentlyMatured = Card::factory()->create(['deck_id' => $this->deck->id]);
    Review::factory()->create([
        'card_id' => $recentlyMatured->id,
        'access_token_id' => $this->token->id,
        'rating' => CardRating::Good,
        'interval_minutes_after' => Card::MATURE_THRESHOLD_MINUTES,
        'reviewed_at' => $this->now->subDays(2),
    ]);

    $longAgoMatured = Card::factory()->create(['deck_id' => $this->deck->id]);
    Review::factory()->create([
        'card_id' => $longAgoMatured->id,
        'access_token_id' => $this->token->id,
        'rating' => CardRating::Good,
        'interval_minutes_after' => Card::MATURE_THRESHOLD_MINUTES,
        'reviewed_at' => $this->now->subDays(30),
    ]);

    $result = app(CountMasteredCards::class)->handle($this->token->id, $this->now);

    expect($result['thisWeek'])->toBe(1);
});

test('a card that later regresses below the threshold still counts by its first mature review', function () {
    $card = Card::factory()->create(['deck_id' => $this->deck->id]);

    Review::factory()->create([
        'card_id' => $card->id,
        'access_token_id' => $this->token->id,
        'rating' => CardRating::Good,
        'interval_minutes_after' => Card::MATURE_THRESHOLD_MINUTES,
        'reviewed_at' => $this->now->subDays(3),
    ]);

    Review::factory()->create([
        'card_id' => $card->id,
        'access_token_id' => $this->token->id,
        'rating' => CardRating::Again,
        'interval_minutes_after' => 1,
        'reviewed_at' => $this->now->subDay(),
    ]);

    $result = app(CountMasteredCards::class)->handle($this->token->id, $this->now);

    expect($result['thisWeek'])->toBe(1);
});
