<?php

use App\Actions\Orchestrators\{AnswerCardOrchestrator, StartStudySessionOrchestrator};
use App\Enums\ReviewResult;
use App\Models\{Card, Deck};
use Livewire\Attributes\{Computed, Layout, Locked, Title};
use Livewire\Component;

new #[Layout('layouts::app')] #[Title('Estudar')] class extends Component
{
    public string $deckName = '';

    #[Locked]
    public ?string $deckSlug = null;

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
        $this->startSession($orchestrator, $deck);
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

        $this->index++;
        $this->revealed = false;
        unset($this->card);
    }

    public function restart(StartStudySessionOrchestrator $orchestrator): void
    {
        $this->startSession($orchestrator, $this->deckSlug);
    }

    private function startSession(StartStudySessionOrchestrator $orchestrator, ?string $deckSlug): void
    {
        $deck = $deckSlug !== null
            ? Deck::where('slug', $deckSlug)
                ->where('access_token_id', session('access_token_id'))
                ->firstOrFail()
            : null;

        $session = $orchestrator
            ->handle((int) session('access_token_id'), $deck);

        $this->deckSlug = $deckSlug;
        $this->deckName = $session->deckName;
        $this->cardIds = $session->cardIds;
        $this->totalCards = count($session->cardIds);
        $this->completedCount = 0;
        $this->index = 0;
        $this->revealed = false;
        unset($this->card);
    }
};
