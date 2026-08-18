<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Card;

final readonly class CountCardsToReinforce
{
    public function handle(int $accessTokenId): int
    {
        return Card::query()
            ->whereHas('deck', fn ($query) => $query->where('access_token_id', $accessTokenId))
            ->toReinforce()
            ->count();
    }
}
