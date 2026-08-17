<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class DashboardData
{
    /**
     * @param  array<int, array{label: string, count: int, percent: int}>  $activity
     * @param  array<int, array{name: string, slug: string, meta: string, icon: string, color: string, urgent: bool, dim: bool}>  $recentDecks
     */
    public function __construct(
        public int $dueToday,
        public int $todayProgressPercent,
        public int $masteredTotal,
        public int $masteredThisWeek,
        public StreakData $streak,
        public array $activity,
        public array $recentDecks,
    ) {}
}
