<?php

use App\Actions\CalculateRecallCounters;
use App\Enums\ReviewResult;

test('remembering increments aced and leaves missed alone', function () {
    $counters = app(CalculateRecallCounters::class)->handle(2, 1, ReviewResult::Remembered);

    expect($counters->acedCount)->toBe(3)
        ->and($counters->missedCount)->toBe(1);
});

test('forgetting increments missed and leaves aced alone', function () {
    $counters = app(CalculateRecallCounters::class)->handle(2, 1, ReviewResult::Forgot);

    expect($counters->acedCount)->toBe(2)
        ->and($counters->missedCount)->toBe(2);
});

test('it works from a fresh, never-studied card', function () {
    $counters = app(CalculateRecallCounters::class)->handle(0, 0, ReviewResult::Remembered);

    expect($counters->acedCount)->toBe(1)
        ->and($counters->missedCount)->toBe(0);
});
