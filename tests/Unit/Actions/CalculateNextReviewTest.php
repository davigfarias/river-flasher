<?php

use App\Actions\CalculateNextReview;
use App\DTO\SrsState;
use App\Enums\CardRating;
use Carbon\CarbonImmutable;

beforeEach(function () {
    $this->action = new CalculateNextReview;
    $this->now = CarbonImmutable::parse('2026-08-17 12:00:00');
});

it('sends a brand-new card to exactly 1m/6m/10m/4d', function (CardRating $rating, int $expectedMinutes) {
    $state = new SrsState(easeFactor: 2.5, intervalMinutes: 0, repetitions: 0);

    $outcome = $this->action->handle($state, $rating, $this->now);

    expect($outcome->intervalMinutes)->toBe($expectedMinutes);
})->with([
    'again -> 1m' => [CardRating::Again, 1],
    'hard -> 6m' => [CardRating::Hard, 6],
    'good -> 10m' => [CardRating::Good, 10],
    'easy -> 4d' => [CardRating::Easy, 5_760],
]);

it('graduates a learning card to 1 day on the second Good, incrementing repetitions', function () {
    $state = new SrsState(easeFactor: 2.5, intervalMinutes: 10, repetitions: 0);

    $outcome = $this->action->handle($state, CardRating::Good, $this->now);

    expect($outcome->intervalMinutes)->toBe(1_440)
        ->and($outcome->repetitions)->toBe(1);
});

it('graduates a learning card straight to 4 days on Easy, incrementing repetitions', function () {
    $state = new SrsState(easeFactor: 2.5, intervalMinutes: 10, repetitions: 0);

    $outcome = $this->action->handle($state, CardRating::Easy, $this->now);

    expect($outcome->intervalMinutes)->toBe(5_760)
        ->and($outcome->repetitions)->toBe(1);
});

it('does not advance repetitions on Hard while still learning', function () {
    $state = new SrsState(easeFactor: 2.5, intervalMinutes: 1, repetitions: 0);

    $outcome = $this->action->handle($state, CardRating::Hard, $this->now);

    expect($outcome->intervalMinutes)->toBe(6)
        ->and($outcome->repetitions)->toBe(0);
});

it('multiplies a reviewing card interval by its ease factor on Good', function () {
    $state = new SrsState(easeFactor: 2.5, intervalMinutes: 1_440, repetitions: 1);

    $outcome = $this->action->handle($state, CardRating::Good, $this->now);

    expect($outcome->intervalMinutes)->toBe(3_600)
        ->and($outcome->repetitions)->toBe(2)
        ->and($outcome->easeFactor)->toBe(2.5);
});

it('applies at least a one-day bump on Hard for a reviewing card, and drops ease', function () {
    $state = new SrsState(easeFactor: 2.5, intervalMinutes: 1_440, repetitions: 1);

    $outcome = $this->action->handle($state, CardRating::Hard, $this->now);

    // max(1440 * 1.2, 1440 + 1440) = max(1728, 2880) = 2880
    expect($outcome->intervalMinutes)->toBe(2_880)
        ->and($outcome->repetitions)->toBe(1)
        ->and($outcome->easeFactor)->toBe(2.35);
});

it('stacks the easy bonus on top of ease for a reviewing card, and raises ease', function () {
    $state = new SrsState(easeFactor: 2.5, intervalMinutes: 1_440, repetitions: 1);

    $outcome = $this->action->handle($state, CardRating::Easy, $this->now);

    // round(1440 * 2.5 * 1.3) = round(4680) = 4680
    expect($outcome->intervalMinutes)->toBe(4_680)
        ->and($outcome->repetitions)->toBe(2)
        ->and($outcome->easeFactor)->toBe(2.65);
});

it('resets a reviewing card to relearning on Again', function () {
    $state = new SrsState(easeFactor: 2.3, intervalMinutes: 4_320, repetitions: 3);

    $outcome = $this->action->handle($state, CardRating::Again, $this->now);

    expect($outcome->intervalMinutes)->toBe(1)
        ->and($outcome->repetitions)->toBe(0)
        ->and($outcome->easeFactor)->toBe(2.1);
});

it('floors ease at 1.3 no matter how many times Again is pressed', function () {
    $state = new SrsState(easeFactor: 1.35, intervalMinutes: 100, repetitions: 2);

    $first = $this->action->handle($state, CardRating::Again, $this->now);
    expect($first->easeFactor)->toBe(1.3);

    $second = $this->action->handle(
        new SrsState(easeFactor: $first->easeFactor, intervalMinutes: $first->intervalMinutes, repetitions: $first->repetitions),
        CardRating::Again,
        $this->now,
    );
    expect($second->easeFactor)->toBe(1.3);
});

it('caps the computed interval at 365 days', function () {
    $state = new SrsState(easeFactor: 2.9, intervalMinutes: 500_000, repetitions: 10);

    $outcome = $this->action->handle($state, CardRating::Easy, $this->now);

    expect($outcome->intervalMinutes)->toBe(525_600);
});

it('sets dueAt to now plus the computed interval', function () {
    $state = new SrsState(easeFactor: 2.5, intervalMinutes: 0, repetitions: 0);

    $outcome = $this->action->handle($state, CardRating::Good, $this->now);

    expect($outcome->dueAt->equalTo($this->now->addMinutes(10)))->toBeTrue();
});
