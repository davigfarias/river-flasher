<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Card;
use Carbon\CarbonImmutable;

final readonly class CountCards
{
    /**
     * @return array{total: int, thisWeek: int}
     */
    public function handle(int $accessTokenId, CarbonImmutable $now): array
    {
        $query = Card::query()->whereHas('deck', fn ($query) => $query->where('access_token_id', $accessTokenId));

        return [
            'total' => (clone $query)->count(),
            'thisWeek' => (clone $query)->where('created_at', '>=', $now->subDays(7))->count(),
        ];
    }
}
