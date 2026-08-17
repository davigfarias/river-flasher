<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTO\ReviewOutcome;
use App\DTO\SrsState;
use App\Enums\CardRating;
use App\Models\Card;
use Carbon\CarbonImmutable;

/**
 * Pure SM-2 (Anki-style) spaced-repetition math: given a card's current
 * ease/interval/repetitions and a rating, computes its next review state.
 *
 * No database access, no clock reads beyond the `$now` argument — the same
 * inputs always produce the same output, which is what makes this safe to
 * reuse both for persisting a real review and for previewing the four
 * button labels ahead of time.
 */
final readonly class CalculateNextReview
{
    /**
     * Ease never drops below this, regardless of how many "Again"s in a row.
     */
    private const float EASE_FLOOR = 1.3;

    /**
     * The learning-phase step between the initial 1-minute step and
     * graduation, reached on the first "Good".
     */
    private const int LEARNING_STEP_MINUTES = 10;

    /**
     * The learning-phase step reached on "Hard", regardless of how far
     * along the card already was.
     */
    private const int HARD_LEARNING_MINUTES = 6;

    /**
     * Interval assigned when "Easy" graduates a learning card directly.
     */
    private const int EASY_GRADUATION_MINUTES = 5_760; // 4 days

    /**
     * Multiplier applied to a reviewing card's interval on "Hard".
     */
    private const float HARD_MULTIPLIER = 1.2;

    /**
     * Extra multiplier stacked on top of the ease factor for "Easy" on a
     * reviewing card.
     */
    private const float EASY_BONUS = 1.3;

    /**
     * Hard ceiling on any computed interval.
     */
    private const int MAX_INTERVAL_MINUTES = 525_600; // 365 days

    public function handle(SrsState $state, CardRating $rating, CarbonImmutable $now): ReviewOutcome
    {
        $isLearning = $state->intervalMinutes < Card::LEARNING_THRESHOLD_MINUTES;

        [$intervalMinutes, $repetitions] = match ($rating) {
            CardRating::Again => [1, 0],

            CardRating::Hard => $isLearning
                ? [self::HARD_LEARNING_MINUTES, $state->repetitions]
                : [
                    max(
                        (int) round($state->intervalMinutes * self::HARD_MULTIPLIER),
                        $state->intervalMinutes + Card::LEARNING_THRESHOLD_MINUTES,
                    ),
                    $state->repetitions,
                ],

            CardRating::Good => $isLearning
                ? ($state->intervalMinutes < self::LEARNING_STEP_MINUTES
                    ? [self::LEARNING_STEP_MINUTES, $state->repetitions]
                    : [Card::LEARNING_THRESHOLD_MINUTES, $state->repetitions + 1])
                : [(int) round($state->intervalMinutes * $state->easeFactor), $state->repetitions + 1],

            CardRating::Easy => $isLearning
                ? [self::EASY_GRADUATION_MINUTES, $state->repetitions + 1]
                : [
                    (int) round($state->intervalMinutes * $state->easeFactor * self::EASY_BONUS),
                    $state->repetitions + 1,
                ],
        };

        $intervalMinutes = max(1, min($intervalMinutes, self::MAX_INTERVAL_MINUTES));

        // Rounded to match the `ease_factor` column's decimal(4,2) storage,
        // and to avoid float noise like 2.3 - 0.20 = 2.0999999999999996.
        $easeFactor = round(max($state->easeFactor + $rating->easeDelta(), self::EASE_FLOOR), 2);

        return new ReviewOutcome(
            easeFactor: $easeFactor,
            intervalMinutes: $intervalMinutes,
            repetitions: $repetitions,
            dueAt: $now->addMinutes($intervalMinutes),
        );
    }
}
