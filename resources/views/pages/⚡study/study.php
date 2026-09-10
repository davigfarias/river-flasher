<?php

use App\Actions\CompareTranslationTokens;
use App\Actions\Orchestrators\{AnswerCardOrchestrator, PrepareTranslationCardOrchestrator, StartStudySessionOrchestrator, UndoAnswerOrchestrator};
use App\DTO\TranslationCardData;
use App\Enums\{ReviewResult, StudyMode};
use App\Models\{Card, Deck, Review};
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\{Computed, Layout, Locked, Title, Url};
use Livewire\Component;

new #[Layout('layouts::app')] #[Title('Estudar')] class extends Component
{
    public string $deckName = '';

    /**
     * The study mode this session runs in — significado (default), leitura or
     * tradução. Kept in the URL so a session can be linked to and `restart()`
     * stays in the same mode.
     */
    #[Url]
    public string $mode = 'meaning';

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
     * Tradução mode only. `translationAttempt` is 0 before the first check
     * and 1 after one wrong try (a second wrong try grades the card as
     * missed). `translationResolved` flips once the card has been graded —
     * correct, or wrong twice — so the UI knows to show "Continuar".
     */
    public int $translationAttempt = 0;

    public ?bool $translationCorrect = null;

    public bool $translationResolved = false;

    /** @var array<int, string> */
    public array $submittedTokens = [];

    /**
     * One entry per answer given this session, in order — a stack `goBack()`
     * pops from to undo the most recent answer and let the user re-grade a
     * card. `previousLastReviewedAt` is kept as an ISO string (not a
     * CarbonImmutable) because Livewire can't hydrate arbitrary objects
     * nested inside a plain array property across requests. `mode` records
     * which counter pair the answer touched so the undo restores the right one.
     *
     * @var array<int, array{index: int, cardId: int, reviewId: int|null, previousAcedCount: int, previousMissedCount: int, previousLastReviewedAt: string|null, requeued: bool, mode: string}>
     */
    public array $history = [];

    public function mount(StartStudySessionOrchestrator $orchestrator, ?string $deck = null): void
    {
        $this->mode = $this->studyMode->value;

        $deckUuids = match (true) {
            $deck !== null => [$deck],
            request()->filled('decks') => array_values(array_filter(explode(',', (string) request()->query('decks')))),
            default => [],
        };

        $this->startSession($orchestrator, $deckUuids);
    }

    #[Computed]
    public function studyMode(): StudyMode
    {
        return StudyMode::tryFrom($this->mode) ?? StudyMode::Meaning;
    }

    #[Computed]
    public function card(): ?Card
    {
        $id = $this->cardIds[$this->index] ?? null;

        return $id ? Card::find($id) : null;
    }

    #[Computed]
    public function translationCard(): ?TranslationCardData
    {
        if ($this->studyMode !== StudyMode::Translation || ! $this->card) {
            return null;
        }

        return app(PrepareTranslationCardOrchestrator::class)->handle($this->card);
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
     * Grades the current card (significado / leitura) and flips it back to its
     * front face, but deliberately does not advance to the next card yet —
     * that happens in `advance()`, called client-side once the flip animation
     * finishes.
     */
    public function answer(string $result, AnswerCardOrchestrator $orchestrator): void
    {
        $card = $this->card;

        if (! $card || $this->studyMode === StudyMode::Translation) {
            return;
        }

        abort_unless($card->deck->access_token_id === session('access_token_id'), 404);

        $this->recordAnswer($card, ReviewResult::from($result), $orchestrator);

        $this->revealed = false;
    }

    /**
     * Tradução mode. Grades the assembled sentence: a match is "acertou"; a
     * first miss just lets the user try again; a second miss is "errou" and
     * reveals the answer. Either way the session then advances client-side.
     *
     * @param  array<int, string>  $tokens
     */
    public function checkTranslation(array $tokens, AnswerCardOrchestrator $orchestrator, CompareTranslationTokens $comparator): void
    {
        $card = $this->card;

        if (! $card || $this->studyMode !== StudyMode::Translation || $this->translationResolved) {
            return;
        }

        abort_unless($card->deck->access_token_id === session('access_token_id'), 404);

        $this->submittedTokens = array_values($tokens);

        $isMatch = $comparator->handle($this->submittedTokens, $this->translationCard?->correctTokens ?? []);

        if ($isMatch) {
            $this->translationCorrect = true;
            $this->translationResolved = true;
            $this->recordAnswer($card, ReviewResult::Remembered, $orchestrator);

            return;
        }

        if ($this->translationAttempt === 0) {
            $this->translationAttempt = 1;
            $this->translationCorrect = false;

            return;
        }

        $this->translationCorrect = false;
        $this->translationResolved = true;
        $this->recordAnswer($card, ReviewResult::Forgot, $orchestrator);
    }

    public function advance(): void
    {
        $this->index++;
        $this->resetCardState();
    }

    /**
     * Undoes the most recently answered card in this session — pops it off
     * `history`, deletes the review it recorded, restores the card's counters
     * for the mode it was answered in, and jumps back to it.
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
            StudyMode::tryFrom($entry['mode'] ?? '') ?? StudyMode::Meaning,
        );

        if ($entry['requeued']) {
            array_pop($this->cardIds);
        } else {
            $this->completedCount--;
        }

        $this->index = $entry['index'];
        $this->resetCardState();
        $this->revealed = true;
    }

    public function restart(StartStudySessionOrchestrator $orchestrator): void
    {
        $this->startSession($orchestrator, $this->deckUuids);
    }

    private function recordAnswer(Card $card, ReviewResult $result, AnswerCardOrchestrator $orchestrator): void
    {
        $mode = $this->studyMode;

        $previousAcedCount = $card->{$mode->acedColumn()};
        $previousMissedCount = $card->{$mode->missedColumn()};
        $previousLastReviewedAt = $card->last_reviewed_at;

        $orchestrator->handle($card, $result, $mode);

        $this->history[] = [
            'index' => $this->index,
            'cardId' => $card->id,
            'reviewId' => Review::where('card_id', $card->id)->latest('id')->value('id'),
            'previousAcedCount' => $previousAcedCount,
            'previousMissedCount' => $previousMissedCount,
            'previousLastReviewedAt' => $previousLastReviewedAt?->toISOString(),
            'requeued' => $result === ReviewResult::Forgot,
            'mode' => $mode->value,
        ];

        if ($result === ReviewResult::Forgot) {
            $this->cardIds[] = $card->id;
        } else {
            $this->completedCount++;
        }
    }

    private function resetCardState(): void
    {
        $this->revealed = false;
        $this->translationAttempt = 0;
        $this->translationCorrect = null;
        $this->translationResolved = false;
        $this->submittedTokens = [];
        unset($this->card, $this->translationCard);
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

        $session = $orchestrator->handle($accessTokenId, $decks, $this->studyMode);

        $this->deckUuids = $deckUuids;
        $this->deckName = $session->deckName;
        $this->cardIds = $session->cardIds;
        $this->totalCards = count($session->cardIds);
        $this->completedCount = 0;
        $this->index = 0;
        $this->history = [];
        $this->resetCardState();
    }
};
