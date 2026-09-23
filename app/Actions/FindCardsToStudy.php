<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\StudyMode;
use App\Models\Card;
use Illuminate\Database\Eloquent\Collection;

final readonly class FindCardsToStudy
{
    /**
     * An empty $deckIds means "across every deck the token owns" — used
     * both for the no-deck "study everything" session and, now, for a
     * custom session spanning several decks at once.
     *
     * Ordering and eligibility follow the study mode: each mode sorts by its
     * own recall counters, and tradução only pulls cards that carry both an
     * example sentence and its translation to build the exercise from.
     * `$onlyStarred` narrows to cards the user starred during a previous
     * study session, for the "estudar com estrela" personalized session.
     * `$paradigmSlug` narrows a tradução session further, to just the cards
     * annotated with that declension (see GetGrammarStudyOptions) — ignored
     * outside tradução mode.
     *
     * @param  array<int, int>  $deckIds
     * @return Collection<int, Card>
     */
    public function handle(
        int $accessTokenId,
        array $deckIds = [],
        int $limit = 50,
        StudyMode $mode = StudyMode::Meaning,
        bool $onlyStarred = false,
        ?string $paradigmSlug = null,
    ): Collection {
        return Card::query()
            ->whereHas('deck', fn ($query) => $query->where('access_token_id', $accessTokenId))
            ->when($deckIds !== [], fn ($query) => $query->whereIn('deck_id', $deckIds))
            ->active()
            ->when($onlyStarred, fn ($query) => $query->starred())
            ->when($mode === StudyMode::Translation, fn ($query) => $query
                ->whereNotNull('example')->where('example', '!=', '')
                ->whereNotNull('translation')->where('translation', '!=', ''))
            ->when($mode === StudyMode::Translation && $paradigmSlug !== null, fn ($query) => $query
                ->where('paradigm_slug', $paradigmSlug))
            ->orderBy($mode->missedColumn(), 'desc')
            ->orderByRaw('last_reviewed_at is not null')
            ->orderByRaw($mode->leastSeenOrderClause())
            ->limit($limit)
            ->get();
    }
}
