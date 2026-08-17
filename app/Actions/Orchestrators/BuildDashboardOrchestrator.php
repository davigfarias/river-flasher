<?php

declare(strict_types=1);

namespace App\Actions\Orchestrators;

use App\Actions\ComputeActivityChart;
use App\Actions\ComputeStudyStreak;
use App\Actions\ComputeTodayProgress;
use App\Actions\CountDueCards;
use App\Actions\CountMasteredCards;
use App\Actions\GetRecentDecksSummary;
use App\DTO\DashboardData;
use App\Enums\ActivityRange;
use Carbon\CarbonImmutable;

final readonly class BuildDashboardOrchestrator
{
    public function __construct(
        private CountDueCards $countDueCards,
        private CountMasteredCards $countMasteredCards,
        private ComputeTodayProgress $computeTodayProgress,
        private ComputeStudyStreak $computeStudyStreak,
        private ComputeActivityChart $computeActivityChart,
        private GetRecentDecksSummary $getRecentDecksSummary,
    ) {}

    public function handle(int $accessTokenId, ActivityRange $range): DashboardData
    {
        $now = CarbonImmutable::now();

        $mastered = $this->countMasteredCards->handle($accessTokenId, $now);

        return new DashboardData(
            dueToday: $this->countDueCards->handle($accessTokenId, $now),
            todayProgressPercent: $this->computeTodayProgress->handle($accessTokenId, $now),
            masteredTotal: $mastered['total'],
            masteredThisWeek: $mastered['thisWeek'],
            streak: $this->computeStudyStreak->handle($accessTokenId, $now),
            activity: $this->computeActivityChart->handle($accessTokenId, $range, $now),
            recentDecks: $this->getRecentDecksSummary->handle($accessTokenId, $now)->all(),
        );
    }
}
