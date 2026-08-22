<?php

use App\Actions\FindCardsByTag;
use App\Actions\GetAvailableTags;
use App\Actions\Orchestrators\CreateDeckFromTagOrchestrator;
use App\DTO\DeckData;
use App\Enums\Language;
use App\Models\{AccessToken, Card};
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Livewire\Attributes\{Computed, Layout, Title};
use Livewire\Component;

new #[Layout('layouts::app')] #[Title('Baralho por tema')] class extends Component
{
    public string $language = 'el';

    public string $tag = '';

    public string $deckName = '';

    /** @var array<int, int> */
    public array $selectedCardIds = [];

    /**
     * @return SupportCollection<int, string>
     */
    #[Computed]
    public function tags(): SupportCollection
    {
        return app(GetAvailableTags::class)->handle(
            (int) session('access_token_id'),
            Language::from($this->language),
        );
    }

    /**
     * @return Collection<int, Card>
     */
    #[Computed]
    public function cards(): Collection
    {
        if ($this->tag === '') {
            return new Collection;
        }

        return app(FindCardsByTag::class)->handle(
            (int) session('access_token_id'),
            Language::from($this->language),
            $this->tag,
        );
    }

    public function updatedLanguage(): void
    {
        $this->tag = '';
        $this->selectedCardIds = [];
        unset($this->tags, $this->cards);
    }

    public function updatedTag(): void
    {
        $this->selectedCardIds = [];
        unset($this->cards);
    }

    public function toggleCard(int $cardId): void
    {
        if (in_array($cardId, $this->selectedCardIds, true)) {
            $this->selectedCardIds = array_values(array_diff($this->selectedCardIds, [$cardId]));

            return;
        }

        $this->selectedCardIds[] = $cardId;
    }

    public function create(CreateDeckFromTagOrchestrator $orchestrator): void
    {
        $this->validate([
            'deckName' => ['required', 'string', 'max:255'],
        ], attributes: ['deckName' => 'nome do baralho']);

        $token = AccessToken::findOrFail(session('access_token_id'));

        $verifiedCardIds = Card::query()
            ->whereIn('id', $this->selectedCardIds)
            ->where('language', $this->language)
            ->whereHas('deck', fn ($query) => $query->where('access_token_id', $token->id))
            ->pluck('id')
            ->all();

        if ($verifiedCardIds === []) {
            Flux::toast(
                heading: 'Nenhum cartão selecionado',
                text: 'Selecione ao menos um cartão para criar o baralho.',
                variant: 'warning',
            );

            return;
        }

        $deck = $orchestrator->handle($token, new DeckData(name: $this->deckName), $verifiedCardIds);

        Flux::toast(
            heading: 'Baralho criado',
            text: '"'.$deck->name.'" foi criado com '.count($verifiedCardIds).' '.(count($verifiedCardIds) === 1 ? 'cartão' : 'cartões').'.',
            variant: 'success',
        );

        $this->redirect(route('decks.show', $deck), navigate: true);
    }
};
