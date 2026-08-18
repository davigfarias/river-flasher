<?php

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
