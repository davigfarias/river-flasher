<?php

declare(strict_types=1);

namespace App\Actions\Orchestrators;

use App\Models\Card;
use App\Models\Review;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Reverts a single `AnswerCardOrchestrator` call: deletes the review it
 * recorded and restores the card's counters/last_reviewed_at to what they
 * were immediately before that answer. The caller (the study page) is
 * responsible for tracking that "before" snapshot per answer, since this
 * orchestrator has no way to derive it from the card's current state alone.
 */
final readonly class UndoAnswerOrchestrator
{
    public function handle(
        Card $card,
        ?int $reviewId,
        int $previousAcedCount,
        int $previousMissedCount,
        ?CarbonImmutable $previousLastReviewedAt,
    ): void {
        DB::transaction(function () use ($card, $reviewId, $previousAcedCount, $previousMissedCount, $previousLastReviewedAt): void {
            if ($reviewId !== null) {
                Review::whereKey($reviewId)->delete();
            }

            $card->update([
                'aced_count' => $previousAcedCount,
                'missed_count' => $previousMissedCount,
                'last_reviewed_at' => $previousLastReviewedAt,
            ]);
        });
    }
}
