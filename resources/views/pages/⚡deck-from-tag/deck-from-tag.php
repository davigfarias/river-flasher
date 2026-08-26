<?php

use App\Actions\FindCardsByCategory;
use App\Actions\FindCardsByTag;
use App\Actions\GetAvailableCategories;
use App\Actions\GetAvailableTags;
use App\Actions\Orchestrators\CreateDeckFromTagOrchestrator;
use App\DTO\DeckData;
use App\Enums\Language;
use App\Models\{AccessToken, Card};
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Livewire\Attributes\{Computed, Layout, Title, Url};
use Livewire\Component;

new #[Layout('layouts::app')] #[Title('Baralho por tema')] class extends Component
{
    /**
     * Which field cards are grouped by: 'pos' (classe gramatical) or
     * 'category' (categoria lexical, e.g. "partes do corpo").
     */
    #[Url]
    public string $by = 'pos';

    public string $language = 'el';

    public string $tag = '';

    public string $deckName = '';

    /** @var array<int, int> */
    public array $selectedCardIds = [];

    public function mount(): void
    {
        if (! in_array($this->by, ['pos', 'category'], true)) {
            $this->by = 'pos';
        }
    }

    public function label(): string
    {
        return $this->by === 'category' ? 'categoria' : 'tema';
    }

    public function labelWithArticle(): string
    {
        return $this->by === 'category' ? 'uma categoria' : 'um tema';
    }

    /**
     * @return SupportCollection<int, string>
     */
    #[Computed]
    public function tags(): SupportCollection
    {
        $accessTokenId = (int) session('access_token_id');
        $language = Language::from($this->language);

        return $this->by === 'category'
            ? app(GetAvailableCategories::class)->handle($accessTokenId, $language)
            : app(GetAvailableTags::class)->handle($accessTokenId, $language);
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

        $accessTokenId = (int) session('access_token_id');
        $language = Language::from($this->language);

        return $this->by === 'category'
            ? app(FindCardsByCategory::class)->handle($accessTokenId, $language, $this->tag)
            : app(FindCardsByTag::class)->handle($accessTokenId, $language, $this->tag);
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
