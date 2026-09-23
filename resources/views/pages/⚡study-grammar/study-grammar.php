<?php

use App\Actions\{GetGrammarStudyOptions, GetParadigm, ListParadigms};
use App\Models\Deck;
use App\Support\Grammar\Paradigm;
use Livewire\Attributes\{Computed, Layout, Locked, Title};
use Livewire\Component;

/**
 * Sits between "escolher Tradução" and the actual /study session: pick a
 * declension, read its table, then continue. Tradução exercises come from
 * the card's own example/translation (see .ai/rules/actions.md), which
 * carries no case tag — declension (paradigm_slug) is the only grammar
 * metadata a card has, so that's what this filters by.
 */
new #[Layout('layouts::app')] #[Title('Tradução — gramática')] class extends Component
{
    #[Locked]
    public ?string $deckUuid = null;

    #[Locked]
    public string $decksQuery = '';

    public string $deckName = 'Todos os baralhos';

    public string $step = 'choose';

    public ?string $paradigmSlug = null;

    /** @var array<string, int> paradigm label => card count, keyed by slug */
    public array $options = [];

    public function mount(GetGrammarStudyOptions $getOptions, ListParadigms $listParadigms, ?string $deck = null): void
    {
        $this->deckUuid = $deck;
        $this->decksQuery = (string) request()->query('decks', '');

        $accessTokenId = (int) session('access_token_id');
        $deckUuids = $deck !== null ? [$deck] : array_values(array_filter(explode(',', $this->decksQuery)));

        $deckIds = $deckUuids === [] ? [] : Deck::query()
            ->whereIn('uuid', $deckUuids)
            ->where('access_token_id', $accessTokenId)
            ->pluck('id')
            ->all();

        $this->deckName = match (count($deckUuids)) {
            0 => 'Todos os baralhos',
            1 => Deck::query()->where('uuid', $deckUuids[0])->where('access_token_id', $accessTokenId)->value('name') ?? 'Baralho',
            default => count($deckUuids).' baralhos selecionados',
        };

        $counts = $getOptions->handle($accessTokenId, $deckIds);

        $this->options = $listParadigms->handle()
            ->filter(fn (Paradigm $paradigm, string $slug) => $counts->has($slug))
            ->map(fn (Paradigm $paradigm) => $paradigm->label)
            ->all();
    }

    public function choose(string $slug): void
    {
        abort_unless(array_key_exists($slug, $this->options), 404);

        $this->paradigmSlug = $slug;
        $this->step = 'theory';
    }

    public function back(): void
    {
        $this->step = 'choose';
        $this->paradigmSlug = null;
    }

    #[Computed]
    public function paradigm(): ?Paradigm
    {
        return $this->paradigmSlug ? app(GetParadigm::class)->handle($this->paradigmSlug) : null;
    }

    public function studyUrl(bool $filtered = true): string
    {
        $params = ['mode' => 'translation'];

        if ($filtered) {
            $params['paradigm'] = $this->paradigmSlug;
        }

        if ($this->deckUuid !== null) {
            $params['deck'] = $this->deckUuid;
        } elseif ($this->decksQuery !== '') {
            $params['decks'] = $this->decksQuery;
        }

        return route('study', $params);
    }
};
