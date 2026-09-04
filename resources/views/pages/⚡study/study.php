<?php

use App\Actions\Orchestrators\{AnswerCardOrchestrator, StartStudySessionOrchestrator};
use App\Enums\ReviewResult;
use App\Models\{Card, Deck};
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

        $orchestrator->handle($card, $result);

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
        unset($this->card);
    }
};
