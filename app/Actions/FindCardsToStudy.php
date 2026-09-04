<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Card;
use Illuminate\Database\Eloquent\Collection;

final readonly class FindCardsToStudy
{
    /**
     * An empty $deckIds means "across every deck the token owns" — used
     * both for the no-deck "study everything" session and, now, for a
     * custom session spanning several decks at once.
     *
     * @param  array<int, int>  $deckIds
     * @return Collection<int, Card>
     */
    public function handle(int $accessTokenId, array $deckIds = [], int $limit = 50): Collection
    {
        return Card::query()
            ->whereHas('deck', fn ($query) => $query->where('access_token_id', $accessTokenId))
            ->when($deckIds !== [], fn ($query) => $query->whereIn('deck_id', $deckIds))
            ->active()
            ->orderByRaw('missed_count desc')
            ->orderByRaw('last_reviewed_at is not null')
            ->orderByRaw('aced_count + missed_count asc')
            ->limit($limit)
            ->get();
    }
}
