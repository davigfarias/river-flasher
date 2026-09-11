<?php

use App\Actions\DeleteDeck;
use App\Actions\ResetTranslationBank;
use App\Actions\SetCardsActiveState;
use App\Actions\ToggleCardActive;
use App\Actions\UpdateDeck;
use App\Livewire\Forms\DeckForm;
use App\Models\{Card, Deck, Sentence, TranslationExercise};
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\{Computed, Layout, Locked, On, Title};
use Livewire\Component;

new #[Layout('layouts::app')] #[Title('Baralho')] class extends Component
{
    public DeckForm $form;

    #[Locked]
    public int $deckId = 0;

    public bool $showInactive = false;

    /** @var array<int, int> */
    public array $selectedCardIds = [];

    public function mount(string $deck): void
    {
        $model = Deck::where('uuid', $deck)
            ->where('access_token_id', session('access_token_id'))
            ->firstOrFail();

        $this->deckId = $model->id;
        $this->form->name = $model->name;
    }

    #[Computed]
    public function deck(): Deck
    {
        return Deck::findOrFail($this->deckId);
    }

    /**
     * Deactivated cards are hidden entirely by default — not just dimmed —
     * so a deck being fed with unfinished cards doesn't look cluttered.
     * `showInactive` lets them be brought back into view to re-activate.
     *
     * @return Collection<int, Card>
     */
    #[Computed]
    public function cards(): Collection
    {
        return $this->deck->cards()
            ->when(! $this->showInactive, fn ($query) => $query->active())
            ->latest()
            ->get();
    }

    #[Computed]
    public function inactiveCount(): int
    {
        return $this->deck->cards()->where('is_active', false)->count();
    }

    #[Computed]
    public function hasApprovedSentences(): bool
    {
        return Sentence::query()->where('deck_id', $this->deckId)->approved()->exists();
    }

    #[Computed]
    public function translationBankCount(): int
    {
        return TranslationExercise::query()
            ->whereIn('card_id', $this->deck->cards()->select('id'))
            ->count();
    }

    public function resetTranslationBank(ResetTranslationBank $action): void
    {
        $count = $action->handle($this->deck);

        Flux::modal('confirm-reset-translation-bank')->close();

        Flux::toast(
            heading: 'Banco de frases resetado',
            text: $count.' '.($count === 1 ? 'frase será regerada' : 'frases serão regeradas').' no próximo treino de tradução.',
            variant: 'success',
        );

        unset($this->translationBankCount);
    }

    public function updatedShowInactive(): void
    {
        $this->selectedCardIds = [];
    }

    public function deactivateSelected(SetCardsActiveState $action): void
    {
        $this->applyBulkActiveState($action, active: false);

        Flux::modal('confirm-deactivate-selected')->close();
    }

    public function activateSelected(SetCardsActiveState $action): void
    {
        $this->applyBulkActiveState($action, active: true);
    }

    private function applyBulkActiveState(SetCardsActiveState $action, bool $active): void
    {
        $verifiedIds = Card::whereIn('id', $this->selectedCardIds)
            ->whereHas('deck', fn ($query) => $query->where('access_token_id', session('access_token_id')))
            ->pluck('id')
            ->all();

        $count = $action->handle($verifiedIds, $active);

        Flux::toast(
            heading: $active ? 'Cartões reativados' : 'Cartões desativados',
            text: $count.' '.($count === 1 ? 'cartão foi' : 'cartões foram').($active ? ' reativado(s).' : ' desativado(s) e não aparecerão mais no estudo.'),
            variant: 'success',
        );

        $this->selectedCardIds = [];

        unset($this->cards, $this->inactiveCount);
    }

    public function deleteDeck(DeleteDeck $action): void
    {
        $cardCount = $this->deck->cards()->count();

        $action->handle($this->deck);

        Flux::modal('confirm-delete-deck')->close();

        Flux::toast(
            heading: 'Baralho excluído',
            text: $cardCount.' '.($cardCount === 1 ? 'cartão foi apagado' : 'cartões foram apagados').' junto com o baralho.',
            variant: 'success',
        );

        $this->redirect(route('decks'), navigate: true);
    }

    public function updateName(UpdateDeck $action): void
    {
        $this->form->validate();

        $action->handle($this->deck, $this->form->toData());

        Flux::toast(heading: 'Nome atualizado', text: 'O baralho foi renomeado.', variant: 'success');

        $this->dispatch('deck-name-updated');
    }

    #[On('card-updated')]
    public function refreshCards(): void
    {
        //
    }

    public function toggleCardActive(int $cardId, ToggleCardActive $action): void
    {
        $card = Card::whereHas(
            'deck',
            fn ($query) => $query->where('access_token_id', session('access_token_id')),
        )->findOrFail($cardId);

        $action->handle($card);

        $this->selectedCardIds = array_values(array_diff($this->selectedCardIds, [$cardId]));

        unset($this->cards, $this->inactiveCount);
    }
};
