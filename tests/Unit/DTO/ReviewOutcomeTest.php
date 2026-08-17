<?php

use App\DTO\ReviewOutcome;
use Carbon\CarbonImmutable;

it('formats the interval label across every scale', function (int $minutes, string $expected) {
    $outcome = new ReviewOutcome(
        easeFactor: 2.5,
        intervalMinutes: $minutes,
        repetitions: 1,
        dueAt: CarbonImmutable::now(),
    );

    expect($outcome->intervalLabel())->toBe($expected);
})->with([
    '1 minute' => [1, '1m'],
    '45 minutes' => [45, '45m'],
    '3 hours' => [180, '3h'],
    '1 day' => [1_440, '1d'],
    '3.5 days' => [5_040, '3.5d'],
    '25 days (still day-scale)' => [36_000, '25d'],
    '30 days (month boundary)' => [43_200, '1mo'],
    '~62.5 days' => [90_000, '2mo'],
]);
