<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTO\ReviewOutcome;
use App\Models\Card;
use Carbon\CarbonImmutable;

final readonly class ApplyReviewToCard
{
    public function handle(Card $card, ReviewOutcome $outcome, CarbonImmutable $now): Card
    {
        $card->update([
            'ease_factor' => $outcome->easeFactor,
            'interval_minutes' => $outcome->intervalMinutes,
            'repetitions' => $outcome->repetitions,
            'due_at' => $outcome->dueAt,
            'last_reviewed_at' => $now,
        ]);

        return $card;
    }
}
