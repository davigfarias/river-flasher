<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Deck;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

final readonly class GetRecentDecksSummary
{
    /**
     * A deck counts as "urgent" once its due pile reaches this size.
     */
    private const int URGENT_DUE_COUNT = 100;

    /**
     * @return Collection<int, array{name: string, slug: string, meta: string, icon: string, color: string, urgent: bool, dim: bool}>
     */
    public function handle(int $accessTokenId, CarbonImmutable $now, int $limit = 4): Collection
    {
        return Deck::query()
            ->where('access_token_id', $accessTokenId)
            ->withCount(['cards as due_count' => fn ($query) => $query->due($now)])
            ->withMax('cards', 'last_reviewed_at')
            ->orderByRaw('cards_max_last_reviewed_at IS NULL, cards_max_last_reviewed_at DESC')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(function (Deck $deck) use ($now): array {
                $dueCount = (int) $deck->getAttribute('due_count');

                return [
                    'name' => $deck->name,
                    'slug' => $deck->slug,
                    'meta' => $this->metaFor($deck, $dueCount, $now),
                    'icon' => $deck->icon,
                    'color' => $deck->color,
                    'urgent' => $dueCount >= self::URGENT_DUE_COUNT,
                    'dim' => $dueCount === 0,
                ];
            });
    }

    private function metaFor(Deck $deck, int $dueCount, CarbonImmutable $now): string
    {
        if ($dueCount > 0) {
            return "{$dueCount} due";
        }

        $reviewedToday = $deck->cards()
            ->whereNotNull('last_reviewed_at')
            ->where('last_reviewed_at', '>=', $now->startOfDay())
            ->exists();

        return $reviewedToday ? 'Completed today' : 'Up to date';
    }
}
