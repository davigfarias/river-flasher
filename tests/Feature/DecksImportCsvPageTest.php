<?php

use App\Enums\Language;
use App\Models\AccessToken;
use App\Models\Card;
use App\Models\Deck;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

beforeEach(function () {
    $this->token = AccessToken::factory()->create();
    session(['access_token_id' => $this->token->id]);
});

function fakeCsv(string $content, string $name = 'cards.csv'): UploadedFile
{
    return UploadedFile::fake()->createWithContent($name, $content);
}

test('uploading a csv populates a preview', function () {
    $csv = fakeCsv("word,definition\nlogos,word\nrhema,utterance\n");

    $component = Livewire::test('pages::decks-import-csv')->set('csvFile', $csv);

    expect($component->instance()->parsedCsv->rows)->toHaveCount(2)
        ->and($component->instance()->parsedCsv->missingColumns)->toBe([]);
});

test('it imports valid rows into an existing deck', function () {
    $deck = Deck::factory()->create(['access_token_id' => $this->token->id]);
    Card::factory()->create(['deck_id' => $deck->id, 'language' => Language::Greek]);

    Livewire::test('pages::decks-import-csv')
        ->set('csvFile', fakeCsv("word,definition\nlogos,word\nrhema,utterance\n"))
        ->set('selectedDeckId', $deck->uuid)
        ->call('import')
        ->assertDispatched('toast-show');

    expect($deck->cards()->count())->toBe(3);
});

test('it creates a new deck when __new__ is selected', function () {
    Livewire::test('pages::decks-import-csv')
        ->set('csvFile', fakeCsv("word,definition,language\nshalom,paz,he\n"))
        ->set('selectedDeckId', '__new__')
        ->set('newDeckName', 'Vocabulário importado')
        ->call('import')
        ->assertHasNoErrors();

    $deck = Deck::where('name', 'Vocabulário importado')->sole();

    expect($deck->access_token_id)->toBe($this->token->id)
        ->and($deck->cards()->sole()->language)->toBe(Language::Hebrew);
});

test('it blocks import when the csv language differs from the deck\'s', function () {
    $deck = Deck::factory()->create(['access_token_id' => $this->token->id]);
    Card::factory()->create(['deck_id' => $deck->id, 'language' => Language::Greek]);

    Livewire::test('pages::decks-import-csv')
        ->set('csvFile', fakeCsv("word,definition,language\nshalom,paz,he\n"))
        ->set('selectedDeckId', $deck->uuid)
        ->call('import')
        ->assertDispatched('toast-show');

    expect($deck->cards()->count())->toBe(1);
});

test('it blocks import when the csv itself mixes languages', function () {
    $deck = Deck::factory()->create(['access_token_id' => $this->token->id]);

    Livewire::test('pages::decks-import-csv')
        ->set('csvFile', fakeCsv("word,definition,language\nlogos,word,el\nshalom,paz,he\n"))
        ->set('selectedDeckId', $deck->uuid)
        ->call('import')
        ->assertDispatched('toast-show');

    expect($deck->cards()->count())->toBe(0);
});

test('rows missing required fields are skipped, valid ones still import', function () {
    $deck = Deck::factory()->create(['access_token_id' => $this->token->id]);

    Livewire::test('pages::decks-import-csv')
        ->set('csvFile', fakeCsv("word,definition,language\nlogos,word,el\n,missing word,el\n"))
        ->set('selectedDeckId', $deck->uuid)
        ->call('import');

    expect($deck->cards()->count())->toBe(1);
});

test('it asks the user to choose a language when neither the deck nor the csv defines one', function () {
    $deck = Deck::factory()->create(['access_token_id' => $this->token->id]);

    Livewire::test('pages::decks-import-csv')
        ->set('csvFile', fakeCsv("word,definition\nlogos,word\n"))
        ->set('selectedDeckId', $deck->uuid)
        ->call('import')
        ->assertDispatched('toast-show');

    expect($deck->cards()->count())->toBe(0);
});
