<?php

use App\Actions\Orchestrators\ImportCardsFromCsvOrchestrator;
use App\Actions\{GetDeckGroupsByLanguage, GetDeckLanguage, ParseCsvCards};
use App\DTO\{DeckData, ParsedCsv};
use App\Enums\Language;
use App\Models\{AccessToken, Deck};
use Flux\Flux;
use Illuminate\Support\Collection as SupportCollection;
use Livewire\Attributes\{Computed, Layout, Title};
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

new #[Layout('layouts::app')] #[Title('Importar CSV')] class extends Component
{
    use WithFileUploads;

    public ?TemporaryUploadedFile $csvFile = null;

    public string $selectedDeckId = '';

    public string $newDeckName = '';

    public ?string $importLanguage = null;

    public ?ParsedCsv $parsedCsv = null;

    /**
     * @return SupportCollection<int, array{label: string, decks: SupportCollection<string, string>}>
     */
    #[Computed]
    public function deckGroups(): SupportCollection
    {
        return app(GetDeckGroupsByLanguage::class)->handle((int) session('access_token_id'));
    }

    #[Computed]
    public function existingDeckLanguage(): ?Language
    {
        $deck = $this->resolveDeck();

        return $deck ? app(GetDeckLanguage::class)->handle($deck) : null;
    }

    /**
     * True once we know we can't resolve an import language from either the
     * destination deck's existing cards or the CSV's own `language` column
     * — the user has to pick one explicitly before importing.
     */
    #[Computed]
    public function needsLanguageChoice(): bool
    {
        return $this->existingDeckLanguage === null && $this->parsedCsv?->detectedLanguage === null;
    }

    public function updatedCsvFile(): void
    {
        $this->parsedCsv = app(ParseCsvCards::class)->handle($this->csvFile->get());
        $this->importLanguage = null;
    }

    public function updatedSelectedDeckId(): void
    {
        unset($this->existingDeckLanguage, $this->needsLanguageChoice);
        $this->importLanguage = null;
    }

    public function import(): void
    {
        $this->validate([
            'csvFile' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        if ($this->selectedDeckId === '') {
            Flux::toast(
                heading: 'Selecione um baralho',
                text: 'Escolha um baralho existente ou crie um novo.',
                variant: 'warning',
            );

            return;
        }

        if ($this->parsedCsv === null || $this->parsedCsv->missingColumns !== []) {
            Flux::toast(
                heading: 'CSV inválido',
                text: $this->parsedCsv?->missingColumns
                    ? 'Coluna(s) obrigatória(s) faltando: '.implode(', ', $this->parsedCsv->missingColumns).'.'
                    : 'Não foi possível ler o arquivo.',
                variant: 'danger',
            );

            return;
        }

        if ($this->parsedCsv->languagesMixed) {
            Flux::toast(
                heading: 'CSV mistura idiomas',
                text: 'Todos os cartões do CSV devem ser do mesmo idioma.',
                variant: 'danger',
            );

            return;
        }

        if ($this->parsedCsv->rows === []) {
            Flux::toast(
                heading: 'Nenhum cartão válido',
                text: 'Todas as linhas do CSV têm campos obrigatórios (palavra/definição) faltando.',
                variant: 'danger',
            );

            return;
        }

        $deck = $this->resolveDeck();
        $existingLanguage = $deck ? app(GetDeckLanguage::class)->handle($deck) : null;

        if ($existingLanguage !== null && $this->parsedCsv->detectedLanguage !== null && $existingLanguage !== $this->parsedCsv->detectedLanguage) {
            Flux::toast(
                heading: 'Idioma diferente do baralho',
                text: 'Este baralho já contém cartões em '.$existingLanguage->label().'. O CSV está em outro idioma.',
                variant: 'danger',
            );

            return;
        }

        $language = $existingLanguage
            ?? $this->parsedCsv->detectedLanguage
            ?? ($this->importLanguage !== null ? Language::from($this->importLanguage) : null);

        if ($language === null) {
            Flux::toast(
                heading: 'Escolha o idioma',
                text: 'O CSV não define um idioma e o baralho ainda não tem cartões. Selecione o idioma antes de importar.',
                variant: 'warning',
            );

            return;
        }

        $newDeckData = null;

        if ($deck === null) {
            $this->validate([
                'newDeckName' => ['required', 'string', 'max:255'],
            ], attributes: ['newDeckName' => 'nome do baralho']);

            $newDeckData = new DeckData(name: $this->newDeckName);
        }

        $token = AccessToken::findOrFail(session('access_token_id'));

        $result = app(ImportCardsFromCsvOrchestrator::class)->handle($token, $deck, $newDeckData, $this->parsedCsv->rows, $language);

        $skipped = count($this->parsedCsv->rowErrors);

        Flux::toast(
            heading: 'Importação concluída',
            text: $result->importedCount.' '.($result->importedCount === 1 ? 'cartão importado' : 'cartões importados').' para "'.$result->deck->name.'"'
                .($skipped > 0 ? '. '.$skipped.' '.($skipped === 1 ? 'linha ignorada' : 'linhas ignoradas').' por falta de campos obrigatórios.' : '.'),
            variant: 'success',
        );

        $this->redirect(route('decks.show', $result->deck), navigate: true);
    }

    private function resolveDeck(): ?Deck
    {
        if ($this->selectedDeckId === '' || $this->selectedDeckId === '__new__') {
            return null;
        }

        return Deck::where('uuid', $this->selectedDeckId)
            ->where('access_token_id', session('access_token_id'))
            ->first();
    }
};
