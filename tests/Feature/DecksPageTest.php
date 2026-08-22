<?php

use App\Enums\Language;
use App\Models\AccessToken;
use App\Models\Card;
use App\Models\Deck;
use Livewire\Livewire;

beforeEach(function () {
    $this->token = AccessToken::factory()->create();

    session(['access_token_id' => $this->token->id]);
});

test('it lists every deck belonging to the token, unlike the dashboard\'s capped list', function () {
    $decks = Deck::factory()->count(5)->create(['access_token_id' => $this->token->id]);

    $component = Livewire::test('pages::decks');

    foreach ($decks as $deck) {
        $component->assertSee($deck->name);
    }
});

test('it never leaks another token\'s decks', function () {
    Deck::factory()->create(['access_token_id' => $this->token->id, 'name' => 'Mine']);

    $otherToken = AccessToken::factory()->create();
    Deck::factory()->create(['access_token_id' => $otherToken->id, 'name' => 'Not Mine']);

    Livewire::test('pages::decks')
        ->assertSee('Mine')
        ->assertDontSee('Not Mine');
});

test('it shows the card count and reinforce badge for each deck', function () {
    $deck = Deck::factory()->create(['access_token_id' => $this->token->id, 'name' => 'Koine Greek']);
    Card::factory()->count(2)->create(['deck_id' => $deck->id]);
    Card::factory()->create(['deck_id' => $deck->id, 'aced_count' => 0, 'missed_count' => 1]);

    Livewire::test('pages::decks')
        ->assertSee('Koine Greek')
        ->assertSee('3 cartões')
        ->assertSee('1 para reforçar');
});

test('an empty deck list shows the empty state with a way to create one', function () {
    Livewire::test('pages::decks')
        ->assertSee('Você ainda não tem nenhum baralho.')
        ->assertSee('Criar primeiro baralho');
});

test('each deck shows a language badge derived from its cards', function () {
    $greekDeck = Deck::factory()->create(['access_token_id' => $this->token->id]);
    Card::factory()->create(['deck_id' => $greekDeck->id, 'language' => Language::Greek]);

    $hebrewDeck = Deck::factory()->create(['access_token_id' => $this->token->id]);
    Card::factory()->create(['deck_id' => $hebrewDeck->id, 'language' => Language::Hebrew]);

    Livewire::test('pages::decks')
        ->assertSee('Grego')
        ->assertSee('Hebraico');
});

test('an empty deck shows no language badge', function () {
    Deck::factory()->create(['access_token_id' => $this->token->id, 'name' => 'Fresh Deck']);

    Livewire::test('pages::decks')
        ->assertSee('Fresh Deck')
        ->assertDontSee('data-flux-badge', false);
});

test('both language toggles are on by default, so every deck shows', function () {
    $greekDeck = Deck::factory()->create(['access_token_id' => $this->token->id, 'name' => 'Koine Greek']);
    Card::factory()->create(['deck_id' => $greekDeck->id, 'language' => Language::Greek]);

    $hebrewDeck = Deck::factory()->create(['access_token_id' => $this->token->id, 'name' => 'Biblical Hebrew']);
    Card::factory()->create(['deck_id' => $hebrewDeck->id, 'language' => Language::Hebrew]);

    Livewire::test('pages::decks')
        ->assertSet('showGreek', true)
        ->assertSet('showHebrew', true)
        ->assertSee('Koine Greek')
        ->assertSee('Biblical Hebrew');
});

test('turning off a language toggle hides decks in that language', function () {
    $greekDeck = Deck::factory()->create(['access_token_id' => $this->token->id, 'name' => 'Koine Greek']);
    Card::factory()->create(['deck_id' => $greekDeck->id, 'language' => Language::Greek]);

    $hebrewDeck = Deck::factory()->create(['access_token_id' => $this->token->id, 'name' => 'Biblical Hebrew']);
    Card::factory()->create(['deck_id' => $hebrewDeck->id, 'language' => Language::Hebrew]);

    Livewire::test('pages::decks')
        ->set('showHebrew', false)
        ->assertSee('Koine Greek')
        ->assertDontSee('Biblical Hebrew');
});

test('a deck with no cards yet is never hidden by the language toggles', function () {
    Deck::factory()->create(['access_token_id' => $this->token->id, 'name' => 'Fresh Deck']);

    Livewire::test('pages::decks')
        ->set('showGreek', false)
        ->set('showHebrew', false)
        ->assertSee('Fresh Deck');
});
