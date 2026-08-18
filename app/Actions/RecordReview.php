<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ReviewResult;
use App\Models\Card;
use App\Models\Review;
use Carbon\CarbonImmutable;

final readonly class RecordReview
{
    public function handle(Card $card, ReviewResult $result, CarbonImmutable $now): Review
    {
        return Review::create([
            'card_id' => $card->id,
            'access_token_id' => $card->deck->access_token_id,
            'result' => $result,
            'reviewed_at' => $now,
        ]);
    }
}
