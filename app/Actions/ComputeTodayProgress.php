<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Card;
use App\Models\Review;
use Carbon\CarbonImmutable;

final readonly class ComputeTodayProgress
{
    public function handle(int $accessTokenId, CarbonImmutable $now): int
    {
        $reviewedToday = Review::query()
            ->where('access_token_id', $accessTokenId)
            ->whereBetween('reviewed_at', [$now->startOfDay(), $now->endOfDay()])
            ->count();

        $dueNow = Card::query()
            ->whereHas('deck', fn ($query) => $query->where('access_token_id', $accessTokenId))
            ->due($now)
            ->count();

        if ($reviewedToday === 0 && $dueNow === 0) {
            return 100;
        }

        return (int) round(($reviewedToday / ($reviewedToday + $dueNow)) * 100);
    }
}
