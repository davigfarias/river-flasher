<?php

use App\Ai\Agents\GreekSentenceWriter;
use App\Enums\Gender;
use App\Enums\Language;
use App\Enums\SentenceSource;
use App\Enums\SentenceStatus;
use App\Models\AccessToken;
use App\Models\Card;
use App\Models\Deck;
use App\Models\Sentence;
use App\Models\SentenceToken;
use Livewire\Livewire;

beforeEach(function () {
    $this->token = AccessToken::factory()->create();
    $this->deck = Deck::factory()->create(['access_token_id' => $this->token->id]);

    session(['access_token_id' => $this->token->id]);
});

test('it shows the oldest pending sentence and hides other tokens\' sentences', function () {
    $mine = Sentence::factory()->create(['access_token_id' => $this->token->id, 'deck_id' => $this->deck->id, 'text' => 'minha frase pendente']);
    Sentence::factory()->approved()->create(['access_token_id' => $this->token->id, 'deck_id' => $this->deck->id, 'text' => 'já aprovada']);
    Sentence::factory()->create(['access_token_id' => AccessToken::factory()->create()->id, 'text' => 'frase de outro token']);

    Livewire::test('pages::sentences-review')
        ->assertSee('minha frase pendente')
        ->assertDontSee('já aprovada')
        ->assertDontSee('frase de outro token')
        ->assertSet('current.id', $mine->id);
});

test('approving sets the status and drops the sentence from the queue', function () {
    $sentence = Sentence::factory()->create(['access_token_id' => $this->token->id, 'deck_id' => $this->deck->id]);

    Livewire::test('pages::sentences-review')
        ->call('approve')
        ->assertSet('current', null);

    expect($sentence->fresh()->status)->toBe(SentenceStatus::Approved);
});

test('rejecting sets the status', function () {
    $sentence = Sentence::factory()->create(['access_token_id' => $this->token->id, 'deck_id' => $this->deck->id]);

    Livewire::test('pages::sentences-review')->call('reject');

    expect($sentence->fresh()->status)->toBe(SentenceStatus::Rejected);
});

test('the translation can be edited inline', function () {
    $sentence = Sentence::factory()->create([
        'access_token_id' => $this->token->id,
        'deck_id' => $this->deck->id,
        'translation_pt' => 'texto antigo',
    ]);

    Livewire::test('pages::sentences-review')
        ->call('startEditingTranslation')
        ->assertSet('translationDraft', 'texto antigo')
        ->set('translationDraft', 'texto novo')
        ->call('saveTranslation')
        ->assertSet('editingTranslation', false);

    expect($sentence->fresh()->translation_pt)->toBe('texto novo');
});

test('the "Gerar frases" action runs the orchestrator and reports the result', function () {
    Card::factory()
        ->declinable('ανθρωπ', 'noun-2-masc', Gender::Masculine)
        ->create(['deck_id' => $this->deck->id, 'word' => 'ἄνθρωπος', 'language' => Language::Greek]);

    GreekSentenceWriter::fake([
        ['sentences' => [validDativeSentence()]],
    ]);

    Livewire::test('pages::sentences-review')
        ->set('genDeck', $this->deck->uuid)
        ->set('genCase', 'dat')
        ->set('genCalls', 1)
        ->call('generate')
        ->assertDispatched('toast-show')
        ->assertSet('lastRun.accepted', 1);

    expect(Sentence::where('source', SentenceSource::Ai)->where('status', SentenceStatus::Pending)->count())->toBe(1);
});

test('a manual sentence is stored pending with whitespace-split tokens', function () {
    Livewire::test('pages::sentences-review')
        ->set('manualDeck', $this->deck->uuid)
        ->set('form.text', 'ὁ ἄνθρωπος γράφει')
        ->set('form.translationPt', 'o homem escreve')
        ->call('storeManual')
        ->assertDispatched('toast-show')
        ->assertHasNoErrors();

    $sentence = Sentence::sole();

    expect($sentence->source)->toBe(SentenceSource::Manual)
        ->and($sentence->status)->toBe(SentenceStatus::Pending)
        ->and($sentence->tokens->pluck('surface')->all())->toBe(['ὁ', 'ἄνθρωπος', 'γράφει'])
        ->and($sentence->tokens->every(fn (SentenceToken $t) => $t->is_target === false))->toBeTrue();
});

test('generate warns instead of crashing when no deck is chosen', function () {
    Livewire::test('pages::sentences-review')
        ->call('generate')
        ->assertDispatched('toast-show');

    expect(Sentence::count())->toBe(0);
});
