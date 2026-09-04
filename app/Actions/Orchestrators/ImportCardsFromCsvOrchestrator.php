<?php

declare(strict_types=1);

namespace App\Actions\Orchestrators;

use App\Actions\CreateDeck;
use App\Actions\InsertCardsBulk;
use App\DTO\CsvImportResult;
use App\DTO\DeckData;
use App\Enums\Language;
use App\Models\AccessToken;
use App\Models\Deck;
use Illuminate\Support\Facades\DB;

final readonly class ImportCardsFromCsvOrchestrator
{
    public function __construct(
        private CreateDeck $createDeck,
        private InsertCardsBulk $insertCardsBulk,
    ) {}

    /**
     * Imports parsed CSV rows into $deck, or into a freshly created deck
     * (named by $newDeckData) when $deck is null.
     *
     * @param  array<int, array{word: string, definition: string, transliteration: ?string, example: ?string, translation: ?string, pos: ?string, category: ?string, isDifficult: bool}>  $rows
     */
    public function handle(AccessToken $token, ?Deck $deck, ?DeckData $newDeckData, array $rows, Language $language): CsvImportResult
    {
        return DB::transaction(function () use ($token, $deck, $newDeckData, $rows, $language): CsvImportResult {
            $deck ??= $this->createDeck->handle($token, $newDeckData);

            $importedCount = $this->insertCardsBulk->handle($deck, $rows, $language);

            return new CsvImportResult($deck, $importedCount);
        });
    }
}
