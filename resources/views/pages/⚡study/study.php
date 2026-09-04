<?php

use App\Actions\Orchestrators\{AnswerCardOrchestrator, StartStudySessionOrchestrator, UndoAnswerOrchestrator};
use App\Enums\ReviewResult;
use App\Models\{Card, Deck, Review};
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\{Computed, Layout, Locked, Title};
use Livewire\Component;

new #[Layout('layouts::app')] #[Title('Estudar')] class extends Component
{
    public string $deckName = '';

    /**
     * The deck uuids this session was built from — empty means "every
     * deck". Kept so `restart()` can rebuild the exact same session
     * instead of falling back to "study everything".
     *
     * @var array<int, string>
     */
    #[Locked]
    public array $deckUuids = [];

    /** @var array<int, int> */
    #[Locked]
    public array $cardIds = [];

    /**
     * Distinct cards in this session, fixed at session start. `cardIds`
     * itself grows as "não lembrei" requeues a card, so it can't be used
     * to report "how many cards" without over-counting.
     */
    #[Locked]
    public int $totalCards = 0;

    public int $completedCount = 0;

    public int $index = 0;

    public bool $revealed = false;

    /**
     * One entry per answer given this session, in order — a stack `goBack()`
     * pops from to undo the most recent answer and let the user re-grade a
     * card. `previousLastReviewedAt` is kept as an ISO string (not a
     * CarbonImmutable) because Livewire can't hydrate arbitrary objects
     * nested inside a plain array property across requests.
     *
     * @var array<int, array{index: int, cardId: int, reviewId: int|null, previousAcedCount: int, previousMissedCount: int, previousLastReviewedAt: string|null, requeued: bool}>
     */
    public array $history = [];

    public function mount(StartStudySessionOrchestrator $orchestrator, ?string $deck = null): void
    {
        $deckUuids = match (true) {
            $deck !== null => [$deck],
            request()->filled('decks') => array_values(array_filter(explode(',', (string) request()->query('decks')))),
            default => [],
        };

        $this->startSession($orchestrator, $deckUuids);
    }

    #[Computed]
    public function card(): ?Card
    {
        $id = $this->cardIds[$this->index] ?? null;

        return $id ? Card::find($id) : null;
    }

    #[Computed]
    public function progress(): int
    {
        return $this->totalCards === 0
            ? 100
            : (int) round(($this->completedCount / $this->totalCards) * 100);
    }

    #[Computed]
    public function canGoBack(): bool
    {
        return $this->history !== [];
    }

    public function reveal(): void
    {
        $this->revealed = true;
    }

    /**
     * Grades the current card and flips it back to its front face, but
     * deliberately does not advance to the next card yet — that happens in
     * `advance()`, called client-side once the flip animation finishes. If
     * both happened together, the DOM diff would swap in the next card's
     * content while the (still-facing-the-user) back face briefly shows it
     * before the flip catches up, spoiling the next answer.
     */
    public function answer(string $result, AnswerCardOrchestrator $orchestrator): void
    {
        $card = $this->card;

        if (! $card) {
            return;
        }

        abort_unless($card->deck->access_token_id === session('access_token_id'), 404);

        $result = ReviewResult::from($result);

        $previousAcedCount = $card->aced_count;
        $previousMissedCount = $card->missed_count;
        $previousLastReviewedAt = $card->last_reviewed_at;

        $orchestrator->handle($card, $result);

        $this->history[] = [
            'index' => $this->index,
            'cardId' => $card->id,
            'reviewId' => Review::where('card_id', $card->id)->latest('id')->value('id'),
            'previousAcedCount' => $previousAcedCount,
            'previousMissedCount' => $previousMissedCount,
            'previousLastReviewedAt' => $previousLastReviewedAt?->toISOString(),
            'requeued' => $result === ReviewResult::Forgot,
        ];

        if ($result === ReviewResult::Forgot) {
            $this->cardIds[] = $card->id;
        } else {
            $this->completedCount++;
        }

        $this->revealed = false;
    }

    public function advance(): void
    {
        $this->index++;
        unset($this->card);
    }

    /**
     * Undoes the most recently answered card in this session — pops it off
     * `history`, deletes the review it recorded, restores the card's
     * counters, and jumps back to it with the answer already showing so the
     * user can pick the correct grade. Lets a user (or their fat-fingered
     * "lembrei"/"não lembrei" tap) correct a mistaken answer without it
     * polluting their recall counters.
     */
    public function goBack(UndoAnswerOrchestrator $orchestrator): void
    {
        if ($this->history === []) {
            return;
        }

        $entry = array_pop($this->history);

        $card = Card::find($entry['cardId']);

        if (! $card) {
            return;
        }

        abort_unless($card->deck->access_token_id === session('access_token_id'), 404);

        $orchestrator->handle(
            $card,
            $entry['reviewId'],
            $entry['previousAcedCount'],
            $entry['previousMissedCount'],
            $entry['previousLastReviewedAt'] ? CarbonImmutable::parse($entry['previousLastReviewedAt']) : null,
        );

        if ($entry['requeued']) {
            array_pop($this->cardIds);
        } else {
            $this->completedCount--;
        }

        $this->index = $entry['index'];
        $this->revealed = true;
        unset($this->card);
    }

    public function restart(StartStudySessionOrchestrator $orchestrator): void
    {
        $this->startSession($orchestrator, $this->deckUuids);
    }

    /**
     * @param  array<int, string>  $deckUuids
     */
    private function startSession(StartStudySessionOrchestrator $orchestrator, array $deckUuids): void
    {
        $accessTokenId = (int) session('access_token_id');

        // A single deck keeps the strict firstOrFail() 404 guard that
        // existing links (deck cards, "estudar este baralho") rely on. A
        // multi-deck custom session just drops any uuid that doesn't
        // resolve, rather than 404ing the whole session over one bad id.
        $decks = match (count($deckUuids)) {
            0 => new Collection,
            1 => new Collection([
                Deck::where('uuid', $deckUuids[0])->where('access_token_id', $accessTokenId)->firstOrFail(),
            ]),
            default => Deck::whereIn('uuid', $deckUuids)->where('access_token_id', $accessTokenId)->get(),
        };

        $session = $orchestrator->handle($accessTokenId, $decks);

        $this->deckUuids = $deckUuids;
        $this->deckName = $session->deckName;
        $this->cardIds = $session->cardIds;
        $this->totalCards = count($session->cardIds);
        $this->completedCount = 0;
        $this->index = 0;
        $this->revealed = false;
        $this->history = [];
        unset($this->card);
    }
};
