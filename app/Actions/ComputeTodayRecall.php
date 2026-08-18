<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ReviewResult;
use App\Models\Review;
use Carbon\CarbonImmutable;

final readonly class ComputeTodayRecall
{
    /**
     * @return array{reviewedToday: int, rememberedPercent: int}
     */
    public function handle(int $accessTokenId, CarbonImmutable $now): array
    {
        $todaysReviews = Review::query()
            ->where('access_token_id', $accessTokenId)
            ->whereBetween('reviewed_at', [$now->startOfDay(), $now->endOfDay()]);

        $reviewedToday = (clone $todaysReviews)->count();

        if ($reviewedToday === 0) {
            return ['reviewedToday' => 0, 'rememberedPercent' => 100];
        }

        $remembered = (clone $todaysReviews)->where('result', ReviewResult::Remembered)->count();

        return [
            'reviewedToday' => $reviewedToday,
            'rememberedPercent' => (int) round(($remembered / $reviewedToday) * 100),
        ];
    }
}
