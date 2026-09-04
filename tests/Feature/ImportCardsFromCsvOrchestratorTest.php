<?php

use App\Actions\Orchestrators\ImportCardsFromCsvOrchestrator;
use App\DTO\DeckData;
use App\Enums\Language;
use App\Models\AccessToken;
use App\Models\Deck;

function csvRow(string $word): array
{
    return ['word' => $word, 'definition' => 'def', 'transliteration' => null, 'example' => null, 'translation' => null, 'pos' => null, 'category' => null, 'isDifficult' => false];
}

test('it imports into an existing deck', function () {
    $token = AccessToken::factory()->create();
    $deck = Deck::factory()->create(['access_token_id' => $token->id]);

    $result = app(ImportCardsFromCsvOrchestrator::class)->handle($token, $deck, null, [csvRow('a'), csvRow('b')], Language::Greek);

    expect($result->deck->id)->toBe($deck->id)
        ->and($result->importedCount)->toBe(2)
        ->and($deck->cards()->count())->toBe(2);
});

test('it creates a new deck when none is given', function () {
    $token = AccessToken::factory()->create();

    $result = app(ImportCardsFromCsvOrchestrator::class)->handle($token, null, new DeckData(name: 'Importado'), [csvRow('a')], Language::Hebrew);

    expect($result->deck->name)->toBe('Importado')
        ->and($result->deck->access_token_id)->toBe($token->id)
        ->and($result->importedCount)->toBe(1);
});
