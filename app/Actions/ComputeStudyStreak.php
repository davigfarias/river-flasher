<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTO\StreakData;
use App\Models\Review;
use Carbon\CarbonImmutable;

final readonly class ComputeStudyStreak
{
    public function handle(int $accessTokenId, CarbonImmutable $now): StreakData
    {
        // Bucketing happens in PHP rather than via DATE()/DATE_FORMAT() in
        // SQL, since those functions aren't portable between SQLite and
        // MySQL. 60 days of history is more than enough for any realistic
        // streak while keeping the row count small.
        $reviewedDates = Review::query()
            ->where('access_token_id', $accessTokenId)
            ->where('reviewed_at', '>=', $now->subDays(60))
            ->pluck('reviewed_at')
            ->map(fn (CarbonImmutable $date): string => $date->format('Y-m-d'))
            ->unique();

        $cursor = $reviewedDates->contains($now->format('Y-m-d')) ? $now : $now->subDay();

        $days = 0;
        while ($reviewedDates->contains($cursor->format('Y-m-d'))) {
            $days++;
            $cursor = $cursor->subDay();
        }

        $last7 = collect(range(6, 0))
            ->map(fn (int $daysAgo): bool => $reviewedDates->contains($now->subDays($daysAgo)->format('Y-m-d')))
            ->all();

        return new StreakData(days: $days, last7: $last7);
    }
}
