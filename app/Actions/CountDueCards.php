<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Card;
use Carbon\CarbonImmutable;

final readonly class CountDueCards
{
    public function handle(int $accessTokenId, CarbonImmutable $now): int
    {
        return Card::query()
            ->whereHas('deck', fn ($query) => $query->where('access_token_id', $accessTokenId))
            ->due($now)
            ->count();
    }
}
