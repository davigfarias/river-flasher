<?php

use App\Actions\GetDeckGroupsByLanguage;
use App\Actions\Orchestrators\GenerateSentencesOrchestrator;
use App\Actions\Orchestrators\StoreManualSentenceOrchestrator;
use App\Enums\GrammaticalCase;
use App\Enums\SentenceStatus;
use App\Livewire\Forms\SentenceForm;
use App\Models\Deck;
use App\Models\Sentence;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Livewire\Attributes\{Computed, Layout, Title};
use Livewire\Component;

new #[Layout('layouts::app')] #[Title('Revisar frases')] class extends Component
{
    public SentenceForm $form;

    public int $index = 0;

    public bool $editingTranslation = false;

    public string $translationDraft = '';

    /** Generation modal. */
    public string $genDeck = '';

    public string $genCase = 'dat';

    public int $genCalls = 3;

    /** Manual-entry modal. */
    public string $manualDeck = '';

    /**
     * The report from the last "Gerar frases" run, kept on screen until
     * the next run.
     *
     * @var array{generated: int, accepted: int, rejected: int, reasons: array<int, string>}|null
     */
    public ?array $lastRun = null;

    /**
     * Decks split into Grego / Hebraico optgroups — same-named decks in
     * different languages (e.g. two "Lição 3") are otherwise indistinguishable
     * in the select.
     *
     * @return SupportCollection<int, array{label: string, decks: SupportCollection<string, string>}>
     */
    #[Computed]
    public function deckGroups(): SupportCollection
    {
        return app(GetDeckGroupsByLanguage::class)->handle((int) session('access_token_id'));
    }

    /**
     * @return Collection<int, Sentence>
     */
    #[Computed]
    public function pending(): Collection
    {
        return Sentence::query()
            ->where('access_token_id', session('access_token_id'))
            ->pending()
            ->with(['tokens.card', 'deck'])
            ->oldest()
            ->get();
    }

    #[Computed]
    public function current(): ?Sentence
    {
        return $this->pending[$this->index] ?? null;
    }

    public function approve(): void
    {
        $this->setStatus(SentenceStatus::Approved);
    }

    public function reject(): void
    {
        $this->setStatus(SentenceStatus::Rejected);
    }

    public function startEditingTranslation(): void
    {
        if (! $this->current) {
            return;
        }

        $this->translationDraft = $this->current->translation_pt;
        $this->editingTranslation = true;
    }

    public function saveTranslation(): void
    {
        $sentence = $this->current;

        if (! $sentence) {
            return;
        }

        $this->validate(['translationDraft' => ['required', 'string', 'max:500']]);

        $sentence->update(['translation_pt' => $this->translationDraft]);

        $this->editingTranslation = false;
        unset($this->pending, $this->current);
    }

    public function generate(GenerateSentencesOrchestrator $orchestrator): void
    {
        $deck = $this->resolveDeck($this->genDeck);

        if (! $deck) {
            Flux::toast(heading: 'Selecione um baralho', text: 'Escolha um baralho antes de continuar.', variant: 'warning');

            return;
        }

        $summary = $orchestrator->handle(
            (int) session('access_token_id'),
            $deck,
            GrammaticalCase::from($this->genCase),
            max(1, min(5, $this->genCalls)),
        );

        unset($this->pending, $this->current);

        if ($summary->blockedReason !== null) {
            Flux::toast(heading: 'Não deu pra gerar', text: $summary->blockedReason, variant: 'warning');

            return;
        }

        $this->lastRun = [
            'generated' => $summary->generated,
            'accepted' => $summary->accepted,
            'rejected' => $summary->rejected,
            'reasons' => $summary->rejectionReasons,
        ];

        Flux::modal('gerar-frases')->close();

        Flux::toast(
            heading: 'Frases geradas',
            text: "{$summary->accepted} aceitas, {$summary->rejected} rejeitadas de {$summary->generated}.",
            variant: $summary->accepted > 0 ? 'success' : 'warning',
        );
    }

    public function storeManual(StoreManualSentenceOrchestrator $orchestrator): void
    {
        $this->form->validate();

        $deck = $this->resolveDeck($this->manualDeck);

        if (! $deck) {
            Flux::toast(heading: 'Selecione um baralho', text: 'Escolha um baralho antes de continuar.', variant: 'warning');

            return;
        }

        $orchestrator->handle((int) session('access_token_id'), $deck, $this->form->toData());

        $this->form->reset();
        unset($this->pending, $this->current);

        Flux::modal('nova-frase')->close();

        Flux::toast(heading: 'Frase adicionada', text: 'Entrou na fila de revisão.', variant: 'success');
    }

    private function setStatus(SentenceStatus $status): void
    {
        $sentence = $this->current;

        if (! $sentence) {
            return;
        }

        $sentence->update(['status' => $status]);

        $this->editingTranslation = false;
        unset($this->pending, $this->current);

        if ($this->index >= $this->pending->count()) {
            $this->index = max(0, $this->pending->count() - 1);
        }
    }

    private function resolveDeck(string $uuid): ?Deck
    {
        return $uuid === ''
            ? null
            : Deck::where('uuid', $uuid)->where('access_token_id', session('access_token_id'))->first();
    }
};
