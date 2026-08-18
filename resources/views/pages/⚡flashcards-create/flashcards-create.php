<?php

use App\Actions\{CreateCard, GetRecentCards};
use App\Livewire\Forms\CardForm;
use App\Models\{Card, Deck};
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Livewire\Attributes\{Computed, Layout, Title};
use Livewire\Component;

new #[Layout('layouts::app')] #[Title('Criar cartão')] class extends Component
{
    public CardForm $form;

    public function mount(): void
    {
        $this->form->deck = $this->decks->keys()->first() ?? '';
    }

    /**
     * @return SupportCollection<string, string>
     */
    #[Computed]
    public function decks(): SupportCollection
    {
        return Deck::query()
            ->where('access_token_id', session('access_token_id'))
            ->orderBy('name')
            ->pluck('name', 'slug');
    }

    /**
     * @return Collection<int, Card>
     */
    #[Computed]
    public function recentCards(): Collection
    {
        $deck = $this->currentDeck();

        return $deck ? app(GetRecentCards::class)->handle($deck) : new Collection;
    }

    public function addToDeck(CreateCard $action): void
    {
        $this->form->validate();

        $deck = $this->currentDeck();

        abort_if(! $deck, 404);

        $action->handle($deck, $this->form->toData());

        Flux::toast(
            heading: 'Cartão adicionado',
            text: 'Salvo em '.$deck->name.'.',
            variant: 'success',
        );

        $this->form->reset(['word', 'transliteration', 'definition', 'example', 'translation', 'pos', 'markDifficult']);
    }

    private function currentDeck(): ?Deck
    {
        return Deck::where('slug', $this->form->deck)
            ->where('access_token_id', session('access_token_id'))
            ->first();
    }
};
