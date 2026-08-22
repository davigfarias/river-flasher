<?php

use App\Actions\{CreateCard, GetDeckLanguage, GetRecentCards, StoreCardImage};
use App\Enums\Language;
use App\Livewire\Forms\CardForm;
use App\Models\{Card, Deck};
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Livewire\Attributes\{Computed, Layout, Title};
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts::app')] #[Title('Criar cartão')] class extends Component
{
    use WithFileUploads;

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
            ->pluck('name', 'uuid');
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

    public function addToDeck(CreateCard $action, GetDeckLanguage $deckLanguage, StoreCardImage $storeCardImage): void
    {
        $this->form->validate();

        $deck = $this->currentDeck();

        abort_if(! $deck, 404);

        $existingLanguage = $deckLanguage->handle($deck);

        if ($existingLanguage !== null && $existingLanguage !== Language::from($this->form->language)) {
            Flux::toast(
                heading: 'Idioma diferente do baralho',
                text: 'Este baralho já contém cartões em '.$existingLanguage->label().'. Escolha outro baralho ou ajuste o idioma.',
                variant: 'danger',
            );

            return;
        }

        $imagePath = $this->form->image ? $storeCardImage->handle($this->form->image) : null;

        $action->handle($deck, $this->form->toData($imagePath));

        Flux::toast(
            heading: 'Cartão adicionado',
            text: 'Salvo em '.$deck->name.'.',
            variant: 'success',
        );

        $this->form->reset(['word', 'transliteration', 'definition', 'example', 'translation', 'pos', 'markDifficult', 'image']);
    }

    private function currentDeck(): ?Deck
    {
        return Deck::where('uuid', $this->form->deck)
            ->where('access_token_id', session('access_token_id'))
            ->first();
    }
};
