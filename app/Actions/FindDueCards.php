<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Card;
use App\Models\Deck;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;

final readonly class FindDueCards
{
    /**
     * @return Collection<int, Card>
     */
    public function handle(int $accessTokenId, ?Deck $deck, CarbonImmutable $now, int $limit = 50): Collection
    {
        return Card::query()
            ->whereHas('deck', fn ($query) => $query->where('access_token_id', $accessTokenId))
            ->when($deck, fn ($query) => $query->where('deck_id', $deck->id))
            ->due($now)
            ->orderBy('due_at')
            ->limit($limit)
            ->get();
    }
}
