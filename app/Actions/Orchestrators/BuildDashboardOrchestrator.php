<?php

declare(strict_types=1);

namespace App\Actions\Orchestrators;

use App\Actions\ComputeActivityChart;
use App\Actions\ComputeStudyStreak;
use App\Actions\ComputeTodayRecall;
use App\Actions\CountCards;
use App\Actions\CountCardsToReinforce;
use App\Actions\GetDecksSummary;
use App\DTO\DashboardData;
use App\Enums\ActivityRange;
use Carbon\CarbonImmutable;

final readonly class BuildDashboardOrchestrator
{
    /**
     * How many recent decks the dashboard's sidebar card shows — see the
     * full list at `/decks` for the rest.
     */
    private const int RECENT_DECKS_LIMIT = 4;

    public function __construct(
        private CountCards $countCards,
        private CountCardsToReinforce $countCardsToReinforce,
        private ComputeTodayRecall $computeTodayRecall,
        private ComputeStudyStreak $computeStudyStreak,
        private ComputeActivityChart $computeActivityChart,
        private GetDecksSummary $getDecksSummary,
    ) {}

    public function handle(int $accessTokenId, ActivityRange $range): DashboardData
    {
        $now = CarbonImmutable::now();

        $cards = $this->countCards->handle($accessTokenId, $now);
        $todayRecall = $this->computeTodayRecall->handle($accessTokenId, $now);

        return new DashboardData(
            totalCards: $cards['total'],
            cardsThisWeek: $cards['thisWeek'],
            toReinforce: $this->countCardsToReinforce->handle($accessTokenId),
            reviewedToday: $todayRecall['reviewedToday'],
            rememberedTodayPercent: $todayRecall['rememberedPercent'],
            streak: $this->computeStudyStreak->handle($accessTokenId, $now),
            activity: $this->computeActivityChart->handle($accessTokenId, $range, $now),
            recentDecks: $this->getDecksSummary->handle($accessTokenId, $now, self::RECENT_DECKS_LIMIT)->all(),
        );
    }
}
