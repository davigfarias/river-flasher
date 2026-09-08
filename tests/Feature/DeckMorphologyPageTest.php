<?php

use App\Enums\Gender;
use App\Enums\Language;
use App\Models\AccessToken;
use App\Models\Card;
use App\Models\Deck;
use Livewire\Livewire;

beforeEach(function () {
    $this->token = AccessToken::factory()->create();
    $this->deck = Deck::factory()->create(['access_token_id' => $this->token->id]);

    session(['access_token_id' => $this->token->id]);
});

test('a deck belonging to another token 404s', function () {
    $otherDeck = Deck::factory()->create(['access_token_id' => AccessToken::factory()->create()->id]);

    $this->get(route('decks.morphology', $otherDeck))->assertNotFound();
});

test('it lists only unannotated Greek cards while "só pendentes" is on', function () {
    $pending = Card::factory()->create(['deck_id' => $this->deck->id, 'language' => Language::Greek, 'word' => 'λόγος']);
    Card::factory()->declinable()->create(['deck_id' => $this->deck->id, 'word' => 'ἔργον']);
    Card::factory()->create(['deck_id' => $this->deck->id, 'language' => Language::Hebrew, 'word' => 'אלף']);

    Livewire::test('pages::deck-morphology', ['deck' => $this->deck->uuid])
        ->assertSee('λόγος')
        ->assertDontSee('ἔργον')
        ->assertDontSee('אלף')
        ->set('onlyPending', false)
        ->assertSee('ἔργον');
});

test('saving writes the four morphology columns and drops the card off the pending list', function () {
    $card = Card::factory()->create(['deck_id' => $this->deck->id, 'language' => Language::Greek, 'word' => 'λόγος']);

    Livewire::test('pages::deck-morphology', ['deck' => $this->deck->uuid])
        ->set("rows.{$card->id}.stem", 'λογ')
        ->set("rows.{$card->id}.paradigm_slug", 'noun-2-masc')
        ->set("rows.{$card->id}.gender", 'masc')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDontSee('λόγος');

    $card->refresh();

    expect($card->stem)->toBe('λογ')
        ->and($card->paradigm_slug)->toBe('noun-2-masc')
        ->and($card->gender)->toBe(Gender::Masculine)
        ->and($card->nom_sg_override)->toBeNull();
});

test('the genitive preview builds from the row stem + paradigm', function () {
    $card = Card::factory()->create(['deck_id' => $this->deck->id, 'language' => Language::Greek]);

    Livewire::test('pages::deck-morphology', ['deck' => $this->deck->uuid])
        ->set("rows.{$card->id}.paradigm_slug", 'noun-2-masc')
        ->set("rows.{$card->id}.stem", 'λογ')
        ->assertSee('λογου');
});

test('a bogus paradigm slug is rejected', function () {
    $card = Card::factory()->create(['deck_id' => $this->deck->id, 'language' => Language::Greek]);

    Livewire::test('pages::deck-morphology', ['deck' => $this->deck->uuid])
        ->set("rows.{$card->id}.paradigm_slug", 'noun-9-bogus')
        ->call('save')
        ->assertHasErrors("rows.{$card->id}.paradigm_slug");
});

test('each card shows its grammatical class as a badge', function () {
    Card::factory()->create([
        'deck_id' => $this->deck->id,
        'language' => Language::Greek,
        'word' => 'καί',
        'pos' => 'Conjunção',
    ]);

    Livewire::test('pages::deck-morphology', ['deck' => $this->deck->uuid])
        ->assertSee('Conjunção');
});

test('the trash icon removes a word from the list and it stays gone on reload', function () {
    $card = Card::factory()->create(['deck_id' => $this->deck->id, 'language' => Language::Greek, 'word' => 'καί']);

    Livewire::test('pages::deck-morphology', ['deck' => $this->deck->uuid])
        ->assertSee('καί')
        ->call('exclude', $card->id)
        ->assertDontSee('καί');

    expect($card->fresh()->morphology_excluded_at)->not->toBeNull();

    Livewire::test('pages::deck-morphology', ['deck' => $this->deck->uuid])
        ->assertDontSee('καί');
});

test('a removed word can be viewed and restored', function () {
    $card = Card::factory()->create([
        'deck_id' => $this->deck->id,
        'language' => Language::Greek,
        'word' => 'καί',
        'morphology_excluded_at' => now(),
    ]);

    Livewire::test('pages::deck-morphology', ['deck' => $this->deck->uuid])
        ->assertDontSee('καί')
        ->set('showExcluded', true)
        ->assertSee('καί')
        ->call('restore', $card->id)
        ->assertSet('showExcluded', false);

    expect($card->fresh()->morphology_excluded_at)->toBeNull();
});

test('a removed word is left out of save and of the annotated count', function () {
    $card = Card::factory()->declinable()->create([
        'deck_id' => $this->deck->id,
        'word' => 'λόγος',
        'morphology_excluded_at' => now(),
    ]);

    $component = Livewire::test('pages::deck-morphology', ['deck' => $this->deck->uuid])
        ->set('onlyPending', false)
        ->assertDontSee('λόγος');

    expect($component->get('annotatedCount'))->toBe(0);
});

test('exclude ignores a card id from another deck', function () {
    $otherDeck = Deck::factory()->create(['access_token_id' => $this->token->id]);
    $foreignCard = Card::factory()->create(['deck_id' => $otherDeck->id, 'language' => Language::Greek]);

    Livewire::test('pages::deck-morphology', ['deck' => $this->deck->uuid])
        ->call('exclude', $foreignCard->id);

    expect($foreignCard->fresh()->morphology_excluded_at)->toBeNull();
});

test('it cannot write morphology onto a card from another deck', function () {
    $otherDeck = Deck::factory()->create(['access_token_id' => $this->token->id]);
    $foreignCard = Card::factory()->create(['deck_id' => $otherDeck->id, 'language' => Language::Greek]);
    $ownCard = Card::factory()->create(['deck_id' => $this->deck->id, 'language' => Language::Greek]);

    Livewire::test('pages::deck-morphology', ['deck' => $this->deck->uuid])
        ->set("rows.{$ownCard->id}.stem", 'λογ')
        ->set("rows.{$ownCard->id}.paradigm_slug", 'noun-2-masc')
        ->set("rows.{$foreignCard->id}.stem", 'hacked')
        ->set("rows.{$foreignCard->id}.paradigm_slug", 'noun-2-masc')
        ->call('save')
        ->assertHasNoErrors();

    expect($foreignCard->fresh()->stem)->toBeNull()
        ->and($ownCard->fresh()->stem)->toBe('λογ');
});
