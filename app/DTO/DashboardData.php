<?php

declare(strict_types=1);

namespace App\DTO;

use Carbon\CarbonImmutable;

final readonly class DashboardData
{
    /**
     * @param  array<int, array{label: string, count: int, percent: int}>  $activity
     * @param  array<int, array{name: string, slug: string, meta: string, urgent: bool, dim: bool, cardsCount: int, lastReviewedAt: ?CarbonImmutable}>  $recentDecks
     */
    public function __construct(
        public int $totalCards,
        public int $cardsThisWeek,
        public int $toReinforce,
        public int $reviewedToday,
        public int $rememberedTodayPercent,
        public StreakData $streak,
        public array $activity,
        public array $recentDecks,
    ) {}
}
