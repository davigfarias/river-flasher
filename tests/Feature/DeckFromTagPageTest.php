<?php

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

test('it lists the tags available for the selected language', function () {
    Card::factory()->create(['deck_id' => $this->deck->id, 'language' => Language::Greek, 'pos' => 'Preposição']);

    Livewire::test('pages::deck-from-tag')
        ->assertSet('language', 'el')
        ->assertSee('Preposição');
});

test('selecting a tag loads its cards', function () {
    $match = Card::factory()->create(['deck_id' => $this->deck->id, 'language' => Language::Greek, 'pos' => 'Preposição', 'word' => 'ἐν']);
    Card::factory()->create(['deck_id' => $this->deck->id, 'language' => Language::Greek, 'pos' => 'Verbo', 'word' => 'λέγω']);

    Livewire::test('pages::deck-from-tag')
        ->set('tag', 'Preposição')
        ->assertSee('ἐν')
        ->assertDontSee('λέγω');
});

test('changing language resets the tag and selection', function () {
    Card::factory()->create(['deck_id' => $this->deck->id, 'language' => Language::Greek, 'pos' => 'Preposição']);

    Livewire::test('pages::deck-from-tag')
        ->set('tag', 'Preposição')
        ->set('language', 'he')
        ->assertSet('tag', '')
        ->assertSet('selectedCardIds', []);
});

test('toggling a card adds and removes it from the selection', function () {
    $card = Card::factory()->create(['deck_id' => $this->deck->id, 'language' => Language::Greek, 'pos' => 'Preposição']);

    Livewire::test('pages::deck-from-tag')
        ->set('tag', 'Preposição')
        ->call('toggleCard', $card->id)
        ->assertSet('selectedCardIds', [$card->id])
        ->call('toggleCard', $card->id)
        ->assertSet('selectedCardIds', []);
});

test('creating the deck moves the selected cards into it and redirects', function () {
    $card = Card::factory()->create(['deck_id' => $this->deck->id, 'language' => Language::Greek, 'pos' => 'Preposição']);
    $other = Card::factory()->create(['deck_id' => $this->deck->id, 'language' => Language::Greek, 'pos' => 'Preposição']);

    Livewire::test('pages::deck-from-tag')
        ->set('tag', 'Preposição')
        ->call('toggleCard', $card->id)
        ->set('deckName', 'Preposições')
        ->call('create')
        ->assertDispatched('toast-show')
        ->assertRedirect();

    $newDeck = Deck::where('name', 'Preposições')->first();

    expect($newDeck)->not->toBeNull()
        ->and($card->fresh()->deck_id)->toBe($newDeck->id)
        ->and($other->fresh()->deck_id)->toBe($this->deck->id);
});

test('a blank deck name is rejected', function () {
    $card = Card::factory()->create(['deck_id' => $this->deck->id, 'language' => Language::Greek, 'pos' => 'Preposição']);

    Livewire::test('pages::deck-from-tag')
        ->set('tag', 'Preposição')
        ->call('toggleCard', $card->id)
        ->call('create')
        ->assertHasErrors(['deckName' => 'required']);
});

test('cards belonging to another token cannot be moved even if the id is tampered with', function () {
    $otherToken = AccessToken::factory()->create();
    $otherDeck = Deck::factory()->create(['access_token_id' => $otherToken->id]);
    $otherCard = Card::factory()->create(['deck_id' => $otherDeck->id, 'language' => Language::Greek]);

    Livewire::test('pages::deck-from-tag')
        ->set('selectedCardIds', [$otherCard->id])
        ->set('deckName', 'Roubado')
        ->call('create')
        ->assertDispatched('toast-show');

    expect($otherCard->fresh()->deck_id)->toBe($otherDeck->id)
        ->and(Deck::where('name', 'Roubado')->exists())->toBeFalse();
});
