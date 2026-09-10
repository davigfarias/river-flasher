<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTO\RecallCounters;
use App\Enums\StudyMode;
use App\Models\Card;
use Carbon\CarbonImmutable;

final readonly class ApplyReviewToCard
{
    /**
     * Writes the new counters onto the pair of columns that belong to the
     * study mode being practised, plus the shared `last_reviewed_at`.
     */
    public function handle(
        Card $card,
        RecallCounters $counters,
        CarbonImmutable $now,
        StudyMode $mode = StudyMode::Meaning,
    ): Card {
        $card->update([
            $mode->acedColumn() => $counters->acedCount,
            $mode->missedColumn() => $counters->missedCount,
            'last_reviewed_at' => $now,
        ]);

        return $card;
    }
}
