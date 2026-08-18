<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Card;
use App\Models\Deck;
use Illuminate\Database\Eloquent\Collection;

final readonly class FindCardsToStudy
{
    /**
     * @return Collection<int, Card>
     */
    public function handle(int $accessTokenId, ?Deck $deck, int $limit = 50): Collection
    {
        return Card::query()
            ->whereHas('deck', fn ($query) => $query->where('access_token_id', $accessTokenId))
            ->when($deck, fn ($query) => $query->where('deck_id', $deck->id))
            ->orderByRaw('missed_count desc')
            ->orderByRaw('last_reviewed_at is not null')
            ->orderByRaw('aced_count + missed_count asc')
            ->limit($limit)
            ->get();
    }
}
