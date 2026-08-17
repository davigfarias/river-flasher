<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Card;
use App\Models\Review;
use Carbon\CarbonImmutable;

final readonly class CountMasteredCards
{
    /**
     * @return array{total: int, thisWeek: int}
     */
    public function handle(int $accessTokenId, CarbonImmutable $now): array
    {
        $total = Card::query()
            ->whereHas('deck', fn ($query) => $query->where('access_token_id', $accessTokenId))
            ->mature()
            ->count();

        // A card "matured this week" if the earliest review that pushed it
        // past the mature threshold happened in the last 7 days. Selecting
        // only the grouped column keeps this portable under MySQL's
        // ONLY_FULL_GROUP_BY mode, not just SQLite.
        $thisWeek = Review::query()
            ->select('card_id')
            ->where('access_token_id', $accessTokenId)
            ->where('interval_minutes_after', '>=', Card::MATURE_THRESHOLD_MINUTES)
            ->groupBy('card_id')
            ->havingRaw('MIN(reviewed_at) >= ?', [$now->subDays(7)])
            ->count();

        return ['total' => $total, 'thisWeek' => $thisWeek];
    }
}
