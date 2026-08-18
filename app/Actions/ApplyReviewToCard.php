<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTO\RecallCounters;
use App\Models\Card;
use Carbon\CarbonImmutable;

final readonly class ApplyReviewToCard
{
    public function handle(Card $card, RecallCounters $counters, CarbonImmutable $now): Card
    {
        $card->update([
            'aced_count' => $counters->acedCount,
            'missed_count' => $counters->missedCount,
            'last_reviewed_at' => $now,
        ]);

        return $card;
    }
}
