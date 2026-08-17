<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTO\ReviewOutcome;
use App\Enums\CardRating;
use App\Models\Card;
use App\Models\Review;
use Carbon\CarbonImmutable;

final readonly class RecordReview
{
    public function handle(Card $card, CardRating $rating, ReviewOutcome $outcome, CarbonImmutable $now): Review
    {
        return Review::create([
            'card_id' => $card->id,
            'access_token_id' => $card->deck->access_token_id,
            'rating' => $rating,
            'ease_factor_after' => $outcome->easeFactor,
            'interval_minutes_after' => $outcome->intervalMinutes,
            'reviewed_at' => $now,
        ]);
    }
}
