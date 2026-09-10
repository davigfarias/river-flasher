<?php

use App\Models\AccessToken;
use App\Models\Card;
use App\Models\Deck;
use Livewire\Livewire;

beforeEach(function () {
    $this->token = AccessToken::factory()->create();
    $this->deck = Deck::factory()->create(['access_token_id' => $this->token->id, 'name' => 'Grego NT']);

    session(['access_token_id' => $this->token->id]);
});

test('opening for a deck resolves its name and offers significado', function () {
    Card::factory()->create(['deck_id' => $this->deck->id, 'transliteration' => null, 'example' => null, 'translation' => null]);

    Livewire::test('study-mode-modal')
        ->call('open', [$this->deck->uuid])
        ->assertSet('deckName', 'Grego NT')
        ->assertSee('Significado')
        ->assertSee(route('study', ['deck' => $this->deck->uuid, 'mode' => 'meaning']), false);
});

test('leitura and tradução links appear only when the deck has the data', function () {
    Card::factory()->count(5)->withSentence()->create([
        'deck_id' => $this->deck->id,
        'transliteration' => 'agápē',
    ]);

    Livewire::test('study-mode-modal')
        ->call('open', [$this->deck->uuid])
        ->assertSee(route('study', ['deck' => $this->deck->uuid, 'mode' => 'reading']), false)
        ->assertSee(route('study', ['deck' => $this->deck->uuid, 'mode' => 'translation']), false);
});

test('tradução is disabled with a hint when too few cards have sentences', function () {
    Card::factory()->count(2)->withSentence()->create(['deck_id' => $this->deck->id]);

    Livewire::test('study-mode-modal')
        ->call('open', [$this->deck->uuid])
        ->assertSee('Precisa de ao menos 5 cartões')
        ->assertDontSee(route('study', ['deck' => $this->deck->uuid, 'mode' => 'translation']), false);
});

test('opening with no deck targets every deck', function () {
    Livewire::test('study-mode-modal')
        ->call('open', [])
        ->assertSet('deckName', 'Todos os baralhos')
        ->assertSee(route('study', ['mode' => 'meaning']), false);
});

test('a multi-deck selection builds a decks= link', function () {
    $other = Deck::factory()->create(['access_token_id' => $this->token->id]);

    Livewire::test('study-mode-modal')
        ->call('open', [$this->deck->uuid, $other->uuid])
        ->assertSet('deckName', '2 baralhos selecionados')
        ->assertSee('mode=meaning', false)
        ->assertSee('decks='.$this->deck->uuid.'%2C'.$other->uuid, false);
});
