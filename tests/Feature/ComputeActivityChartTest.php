<?php

use App\Actions\ComputeActivityChart;
use App\Enums\ActivityRange;
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

test('an empty history returns a fully zeroed chart with no division errors', function () {
    $activity = app(ComputeActivityChart::class)->handle($this->token->id, ActivityRange::Week, $this->now);

    expect($activity)->toHaveCount(7)
        ->and(collect($activity)->pluck('count')->all())->toBe([0, 0, 0, 0, 0, 0, 0])
        ->and(collect($activity)->pluck('percent')->all())->toBe([0, 0, 0, 0, 0, 0, 0]);
});

test('the 7-day range buckets by day and normalizes the tallest bar to 100%', function () {
    ($this->reviewOn)($this->now); // today: 1
    ($this->reviewOn)($this->now->subDays(2));
    ($this->reviewOn)($this->now->subDays(2));
    ($this->reviewOn)($this->now->subDays(2)); // 2 days ago: 3 (the max)

    $activity = app(ComputeActivityChart::class)->handle($this->token->id, ActivityRange::Week, $this->now);

    expect($activity)->toHaveCount(7);

    $byLabel = collect($activity)->keyBy('label');

    expect($byLabel['Mon']['count'])->toBe(1) // today
        ->and($byLabel['Mon']['percent'])->toBe(33)
        ->and($byLabel['Sat']['count'])->toBe(3) // 2 days before Monday
        ->and($byLabel['Sat']['percent'])->toBe(100);
});

test('the 30-day range buckets into 4 weekly buckets', function () {
    ($this->reviewOn)($this->now); // this week -> W4
    ($this->reviewOn)($this->now->subDays(27)); // the oldest day in window -> W1

    $activity = app(ComputeActivityChart::class)->handle($this->token->id, ActivityRange::Month, $this->now);

    expect($activity)->toHaveCount(4)
        ->and(collect($activity)->pluck('label')->all())->toBe(['W1', 'W2', 'W3', 'W4']);

    $byLabel = collect($activity)->keyBy('label');

    expect($byLabel['W1']['count'])->toBe(1)
        ->and($byLabel['W4']['count'])->toBe(1);
});

test('the all-time range buckets by month over the last 6 months', function () {
    ($this->reviewOn)($this->now); // current month
    ($this->reviewOn)($this->now->subMonths(5)); // oldest month in window

    $activity = app(ComputeActivityChart::class)->handle($this->token->id, ActivityRange::All, $this->now);

    expect($activity)->toHaveCount(6)
        ->and($activity[0]['label'])->toBe($this->now->subMonths(5)->format('M'))
        ->and($activity[0]['count'])->toBe(1)
        ->and($activity[5]['label'])->toBe($this->now->format('M'))
        ->and($activity[5]['count'])->toBe(1);
});

test('reviews outside the range window are excluded', function () {
    ($this->reviewOn)($this->now->subDays(10)); // outside the 7-day window

    $activity = app(ComputeActivityChart::class)->handle($this->token->id, ActivityRange::Week, $this->now);

    expect(collect($activity)->sum('count'))->toBe(0);
});
