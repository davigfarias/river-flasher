<?php

use App\Actions\ComputeStudyStreak;
use App\Models\AccessToken;
use App\Models\Card;
use App\Models\Deck;
use App\Models\Review;
use Carbon\CarbonImmutable;

beforeEach(function () {
    $this->token = AccessToken::factory()->create();
    $this->deck = Deck::factory()->create(['access_token_id' => $this->token->id]);
    $this->now = CarbonImmutable::parse('2026-08-17 12:00:00'); // a Monday

    $this->reviewOn = function (CarbonImmutable $when) {
        $card = Card::factory()->create(['deck_id' => $this->deck->id]);

        Review::factory()->create([
            'card_id' => $card->id,
            'access_token_id' => $this->token->id,
            'reviewed_at' => $when,
        ]);
    };
});

test('no reviews means no streak', function () {
    $streak = app(ComputeStudyStreak::class)->handle($this->token->id, $this->now);

    expect($streak->days)->toBe(0)
        ->and($streak->last7)->toBe([false, false, false, false, false, false, false]);
});

test('a review today alone is a streak of 1', function () {
    ($this->reviewOn)($this->now);

    $streak = app(ComputeStudyStreak::class)->handle($this->token->id, $this->now);

    expect($streak->days)->toBe(1)
        ->and($streak->last7)->toBe([false, false, false, false, false, false, true]);
});

test('yesterday and today make a streak of 2', function () {
    ($this->reviewOn)($this->now);
    ($this->reviewOn)($this->now->subDay());

    $streak = app(ComputeStudyStreak::class)->handle($this->token->id, $this->now);

    expect($streak->days)->toBe(2);
});

test('a gap breaks the streak', function () {
    ($this->reviewOn)($this->now);
    ($this->reviewOn)($this->now->subDays(2)); // gap at -1

    $streak = app(ComputeStudyStreak::class)->handle($this->token->id, $this->now);

    expect($streak->days)->toBe(1);
});

test('reviewing yesterday but not yet today still counts the streak', function () {
    ($this->reviewOn)($this->now->subDay());

    $streak = app(ComputeStudyStreak::class)->handle($this->token->id, $this->now);

    expect($streak->days)->toBe(1)
        ->and($streak->last7)->toBe([false, false, false, false, false, true, false]);
});
