<?php

use App\Actions\ComputeTodayRecall;
use App\Enums\ReviewResult;
use App\Models\AccessToken;
use App\Models\Card;
use App\Models\Deck;
use App\Models\Review;
use Carbon\CarbonImmutable;

beforeEach(function () {
    $this->token = AccessToken::factory()->create();
    $this->deck = Deck::factory()->create(['access_token_id' => $this->token->id]);
    $this->now = CarbonImmutable::now();

    $this->reviewToday = function (ReviewResult $result) {
        $card = Card::factory()->create(['deck_id' => $this->deck->id]);

        Review::factory()->create([
            'card_id' => $card->id,
            'access_token_id' => $this->token->id,
            'result' => $result,
            'reviewed_at' => $this->now,
        ]);
    };
});

test('no reviews today returns 100% with zero reviewed', function () {
    $result = app(ComputeTodayRecall::class)->handle($this->token->id, $this->now);

    expect($result)->toBe(['reviewedToday' => 0, 'rememberedPercent' => 100]);
});

test('it computes the percentage remembered out of today\'s reviews', function () {
    ($this->reviewToday)(ReviewResult::Remembered);
    ($this->reviewToday)(ReviewResult::Remembered);
    ($this->reviewToday)(ReviewResult::Remembered);
    ($this->reviewToday)(ReviewResult::Forgot);

    $result = app(ComputeTodayRecall::class)->handle($this->token->id, $this->now);

    expect($result)->toBe(['reviewedToday' => 4, 'rememberedPercent' => 75]);
});

test('reviews from other days do not count', function () {
    $card = Card::factory()->create(['deck_id' => $this->deck->id]);
    Review::factory()->create([
        'card_id' => $card->id,
        'access_token_id' => $this->token->id,
        'reviewed_at' => $this->now->subDay(),
    ]);

    $result = app(ComputeTodayRecall::class)->handle($this->token->id, $this->now);

    expect($result)->toBe(['reviewedToday' => 0, 'rememberedPercent' => 100]);
});
