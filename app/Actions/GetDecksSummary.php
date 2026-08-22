<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\Language;
use App\Models\Card;
use App\Models\Deck;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

final readonly class GetDecksSummary
{
    /**
     * A deck counts as "urgent" once its reinforce pile reaches this size.
     */
    private const int URGENT_REINFORCE_COUNT = 20;

    /**
     * @return Collection<int, array{name: string, uuid: string, meta: string, language: ?Language, urgent: bool, dim: bool, cardsCount: int, lastReviewedAt: ?CarbonImmutable}>
     */
    public function handle(int $accessTokenId, CarbonImmutable $now, ?int $limit = null): Collection
    {
        return Deck::query()
            ->where('access_token_id', $accessTokenId)
            ->withCount('cards')
            ->withCount(['cards as reinforce_count' => fn ($query) => $query->active()->toReinforce()])
            ->withMax('cards', 'last_reviewed_at')
            ->addSelect(['language' => Card::query()
                ->select('language')
                ->whereColumn('deck_id', 'decks.id')
                ->orderBy('id')
                ->limit(1),
            ])
            ->orderByRaw('cards_max_last_reviewed_at IS NULL, cards_max_last_reviewed_at DESC')
            ->orderByDesc('created_at')
            ->when($limit !== null, fn ($query) => $query->limit($limit))
            ->get()
            ->map(fn (Deck $deck) => $this->summarize($deck, $now));
    }

    /**
     * @return array{name: string, uuid: string, meta: string, language: ?Language, urgent: bool, dim: bool, cardsCount: int, lastReviewedAt: ?CarbonImmutable}
     */
    private function summarize(Deck $deck, CarbonImmutable $now): array
    {
        $reinforceCount = (int) $deck->getAttribute('reinforce_count');
        $lastReviewedAt = $deck->getAttribute('cards_max_last_reviewed_at');
        $language = $deck->getAttribute('language');

        return [
            'name' => $deck->name,
            'uuid' => $deck->uuid,
            'meta' => $this->metaFor($deck, $reinforceCount, $now),
            'language' => is_string($language) ? Language::from($language) : null,
            'urgent' => $reinforceCount >= self::URGENT_REINFORCE_COUNT,
            'dim' => $reinforceCount === 0,
            'cardsCount' => (int) $deck->getAttribute('cards_count'),
            'lastReviewedAt' => $lastReviewedAt ? CarbonImmutable::parse($lastReviewedAt) : null,
        ];
    }

    private function metaFor(Deck $deck, int $reinforceCount, CarbonImmutable $now): string
    {
        if ($reinforceCount > 0) {
            return "{$reinforceCount} para reforçar";
        }

        $reviewedToday = $deck->cards()
            ->whereNotNull('last_reviewed_at')
            ->where('last_reviewed_at', '>=', $now->startOfDay())
            ->exists();

        return $reviewedToday ? 'Revisado hoje' : 'Em dia';
    }
}
