<?php

use App\Actions\GenerateInflectedForm;
use App\Actions\GetParadigm;
use App\Actions\ListParadigms;
use App\Actions\Orchestrators\SaveCardsMorphologyOrchestrator;
use App\Actions\SetCardMorphologyExclusion;
use App\DTO\CardMorphology;
use App\Enums\Gender;
use App\Enums\GrammaticalCase;
use App\Enums\GrammaticalNumber;
use App\Enums\Language;
use App\Models\Card;
use App\Models\Deck;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\{Computed, Layout, Locked, Title};
use Livewire\Component;

new #[Layout('layouts::app')] #[Title('Anotar morfologia')] class extends Component
{
    #[Locked]
    public int $deckId = 0;

    /**
     * Only Greek cards can be declined — the paradigms live in
     * config/grammar/greek.php.
     */
    public bool $onlyPending = true;

    /**
     * When on, the list shows only the cards that were removed from
     * annotation (via the trash icon), each with a restore button.
     */
    public bool $showExcluded = false;

    /**
     * One row of inputs per card, keyed by card id. Seeded from the card's
     * current columns in mount() and written back on save().
     *
     * @var array<int, array{stem: string, paradigm_slug: string, gender: string, nom_sg_override: string}>
     */
    public array $rows = [];

    public function mount(string $deck): void
    {
        $model = Deck::where('uuid', $deck)
            ->where('access_token_id', session('access_token_id'))
            ->firstOrFail();

        $this->deckId = $model->id;

        foreach ($this->greekCards() as $card) {
            $this->rows[$card->id] = [
                'stem' => $card->stem ?? '',
                'paradigm_slug' => $card->paradigm_slug ?? '',
                'gender' => $card->gender?->value ?? '',
                'nom_sg_override' => $card->nom_sg_override ?? '',
            ];
        }
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
        if ($this->showExcluded) {
            return $this->greekCards()->whereNotNull('morphology_excluded_at')->values();
        }

        return $this->greekCards()
            ->whereNull('morphology_excluded_at')
            ->when($this->onlyPending, fn ($cards) => $cards->whereNull('paradigm_slug'))
            ->values();
    }

    #[Computed]
    public function annotatedCount(): int
    {
        return $this->greekCards()
            ->whereNull('morphology_excluded_at')
            ->whereNotNull('paradigm_slug')
            ->count();
    }

    #[Computed]
    public function excludedCount(): int
    {
        return $this->greekCards()->whereNotNull('morphology_excluded_at')->count();
    }

    /**
     * @return SupportCollection<string, string>  slug => label
     */
    #[Computed]
    public function paradigmOptions(): SupportCollection
    {
        return app(ListParadigms::class)->handle()->map(fn ($paradigm) => $paradigm->label);
    }

    /**
     * Slugs whose nominative singular the stem can't predict — those rows
     * show the nom_sg_override input.
     *
     * @return array<int, string>
     */
    #[Computed]
    public function slugsNeedingOverride(): array
    {
        return app(ListParadigms::class)->handle()
            ->filter(fn ($paradigm) => $paradigm->endingFor(GrammaticalCase::Nominative, GrammaticalNumber::Singular) === null)
            ->keys()
            ->all();
    }

    /**
     * Live "does the stem look right?" check: the genitive singular built
     * from the row's current stem + paradigm. Empty until both are set.
     */
    public function genitivePreview(int $cardId): string
    {
        $row = $this->rows[$cardId] ?? null;

        if (! $row || $row['stem'] === '' || $row['paradigm_slug'] === '') {
            return '';
        }

        try {
            $paradigm = app(GetParadigm::class)->handle($row['paradigm_slug']);
        } catch (\Throwable) {
            return '';
        }

        return app(GenerateInflectedForm::class)->handle(
            $row['stem'],
            $paradigm,
            GrammaticalCase::Genitive,
            GrammaticalNumber::Singular,
        );
    }

    /**
     * Remove a word from the annotation list — the user has decided it
     * doesn't decline. Reversible from the "Removidas" view.
     */
    public function exclude(int $cardId, SetCardMorphologyExclusion $action): void
    {
        $this->setExclusion($cardId, true, $action);
    }

    public function restore(int $cardId, SetCardMorphologyExclusion $action): void
    {
        $this->setExclusion($cardId, false, $action);
    }

    public function save(SaveCardsMorphologyOrchestrator $orchestrator): void
    {
        if ($this->showExcluded) {
            return;
        }

        $slugs = $this->paradigmOptions->keys()->all();

        $this->validate([
            'rows.*.stem' => ['nullable', 'string', 'max:255'],
            'rows.*.paradigm_slug' => ['nullable', Rule::in($slugs)],
            'rows.*.gender' => ['nullable', Rule::in(array_column(Gender::cases(), 'value'))],
            'rows.*.nom_sg_override' => ['nullable', 'string', 'max:255'],
        ]);

        // Only the cards this screen actually renders — a row injected for
        // any other id (another deck, a non-Greek card) is ignored here,
        // and the orchestrator scopes by deck as a second guard.
        $annotations = $this->cards
            ->mapWithKeys(function (Card $card): array {
                $row = $this->rows[$card->id] ?? [];

                return [$card->id => new CardMorphology(
                    stem: ($row['stem'] ?? '') !== '' ? $row['stem'] : null,
                    paradigmSlug: ($row['paradigm_slug'] ?? '') !== '' ? $row['paradigm_slug'] : null,
                    gender: ($row['gender'] ?? '') !== '' ? Gender::from($row['gender']) : null,
                    nomSgOverride: ($row['nom_sg_override'] ?? '') !== '' ? $row['nom_sg_override'] : null,
                )];
            })
            ->all();

        $count = $orchestrator->handle($this->deck, $annotations);

        unset($this->cards, $this->annotatedCount);

        Flux::toast(
            heading: 'Morfologia salva',
            text: $count.' '.($count === 1 ? 'cartão anotado' : 'cartões anotados').'.',
            variant: 'success',
        );
    }

    private function setExclusion(int $cardId, bool $excluded, SetCardMorphologyExclusion $action): void
    {
        $card = $this->greekCards()->firstWhere('id', $cardId);

        if (! $card) {
            return;
        }

        $action->handle($card, $excluded);

        unset($this->cards, $this->annotatedCount, $this->excludedCount);

        if ($this->showExcluded && $this->excludedCount === 0) {
            $this->showExcluded = false;
        }
    }

    /**
     * @return Collection<int, Card>
     */
    private function greekCards(): Collection
    {
        return Card::query()
            ->where('deck_id', $this->deckId)
            ->where('language', Language::Greek)
            ->orderBy('word')
            ->get();
    }
};
