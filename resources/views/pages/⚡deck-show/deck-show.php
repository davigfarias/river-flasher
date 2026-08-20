<?php

use App\Actions\UpdateDeck;
use App\Livewire\Forms\DeckForm;
use App\Models\{Card, Deck};
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\{Computed, Layout, Locked, On, Title};
use Livewire\Component;

new #[Layout('layouts::app')] #[Title('Baralho')] class extends Component
{
    public DeckForm $form;

    #[Locked]
    public int $deckId = 0;

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
     * @return Collection<int, Card>
     */
    #[Computed]
    public function cards(): Collection
    {
        return $this->deck->cards()->latest()->get();
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
};
