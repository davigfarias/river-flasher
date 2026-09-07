<?php

use App\Actions\Orchestrators\BuildDrillItemOrchestrator;
use App\DTO\DrillItem;
use App\Models\Deck;
use App\Support\Grammar\GreekFormComparator;
use Livewire\Attributes\{Computed, Layout, Locked, Title};
use Livewire\Component;

new #[Layout('layouts::app')] #[Title('Praticar')] class extends Component
{
    #[Locked]
    public int $deckId = 0;

    #[Locked]
    public string $deckUuid = '';

    #[Locked]
    public string $deckName = '';

    #[Locked]
    public int $total = 12;

    public int $completed = 0;

    public ?DrillItem $item = null;

    public bool $answered = false;

    public ?string $chosen = null;

    public bool $lastCorrect = false;

    /**
     * "caso número" of every miss this session, for the closing summary.
     *
     * @var array<int, string>
     */
    public array $misses = [];

    public function mount(string $deck, BuildDrillItemOrchestrator $orchestrator): void
    {
        $model = Deck::where('uuid', $deck)
            ->where('access_token_id', session('access_token_id'))
            ->firstOrFail();

        $this->deckId = $model->id;
        $this->deckUuid = $model->uuid;
        $this->deckName = $model->name;

        $this->item = $orchestrator->handle($this->deckId);
    }

    #[Computed]
    public function finished(): bool
    {
        return $this->completed >= $this->total
            || ($this->item === null && $this->completed > 0);
    }

    #[Computed]
    public function correctCount(): int
    {
        return $this->completed - count($this->misses);
    }

    public function choose(string $option, GreekFormComparator $comparator): void
    {
        if ($this->answered || $this->item === null) {
            return;
        }

        $this->chosen = $option;
        $this->lastCorrect = $comparator->matches($this->item->correctSurface, $option);
        $this->answered = true;

        if (! $this->lastCorrect) {
            $this->misses[] = $this->item->analysis;
        }
    }

    public function next(BuildDrillItemOrchestrator $orchestrator): void
    {
        if (! $this->answered) {
            return;
        }

        $this->completed++;
        $this->answered = false;
        $this->chosen = null;
        $this->lastCorrect = false;
        unset($this->finished, $this->correctCount);

        // Stop once the session is full; otherwise pull the next item. A
        // null here (pool ran dry) also ends the session, via `finished`.
        $this->item = $this->completed >= $this->total
            ? null
            : $orchestrator->handle($this->deckId);
    }

    public function restart(BuildDrillItemOrchestrator $orchestrator): void
    {
        $this->completed = 0;
        $this->answered = false;
        $this->chosen = null;
        $this->lastCorrect = false;
        $this->misses = [];
        $this->item = $orchestrator->handle($this->deckId);
    }
};
