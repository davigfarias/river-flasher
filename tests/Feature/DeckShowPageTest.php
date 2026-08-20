<?php

use App\Enums\Language;
use App\Models\AccessToken;
use App\Models\Card;
use App\Models\Deck;
use Livewire\Livewire;

beforeEach(function () {
    $this->token = AccessToken::factory()->create();
    $this->deck = Deck::factory()->create(['access_token_id' => $this->token->id, 'name' => 'Koine Greek']);

    session(['access_token_id' => $this->token->id]);
});

test('it shows every card with its full content', function () {
    Card::factory()->create([
        'deck_id' => $this->deck->id,
        'language' => Language::Greek,
        'pos' => 'Noun',
        'word' => 'ἀγάπη',
        'transliteration' => 'agápē',
        'definition' => 'Selfless, unconditional love.',
        'example' => 'ἡ ἀγάπη μακροθυμεῖ.',
        'translation' => 'Love is patient.',
    ]);

    Livewire::test('pages::deck-show', ['deck' => $this->deck->uuid])
        ->assertSee('ἀγάπη')
        ->assertSee('agápē')
        ->assertSee('Noun')
        ->assertSee('Selfless, unconditional love.')
        ->assertSee('ἡ ἀγάπη μακροθυμεῖ.')
        ->assertSee('Love is patient.');
});

test('a deck uuid belonging to another token 404s', function () {
    $otherToken = AccessToken::factory()->create();
    $otherDeck = Deck::factory()->create(['access_token_id' => $otherToken->id]);

    $this->get('/decks/'.$otherDeck->uuid)->assertNotFound();
});

test('a malformed uuid 404s', function () {
    $this->get('/decks/not-a-uuid')->assertNotFound();
});

test('it renders hebrew cards with rtl direction and the hebrew font', function () {
    Card::factory()->create([
        'deck_id' => $this->deck->id,
        'language' => Language::Hebrew,
        'word' => 'שָׁלוֹם',
    ]);

    Livewire::test('pages::deck-show', ['deck' => $this->deck->uuid])
        ->assertSee('dir="rtl"', false)
        ->assertSee('font-hebrew', false);
});

test('updating the name persists and dispatches a success toast', function () {
    Livewire::test('pages::deck-show', ['deck' => $this->deck->uuid])
        ->set('form.name', 'Renamed Deck')
        ->call('updateName')
        ->assertDispatched('toast-show')
        ->assertDispatched('deck-name-updated');

    expect($this->deck->fresh()->name)->toBe('Renamed Deck');
});

test('a blank name is rejected', function () {
    Livewire::test('pages::deck-show', ['deck' => $this->deck->uuid])
        ->set('form.name', '')
        ->call('updateName')
        ->assertHasErrors(['form.name' => 'required']);

    expect($this->deck->fresh()->name)->toBe('Koine Greek');
});

test('the empty state appears for a deck without cards', function () {
    Livewire::test('pages::deck-show', ['deck' => $this->deck->uuid])
        ->assertSee('Este baralho ainda não tem cartões.');
});
