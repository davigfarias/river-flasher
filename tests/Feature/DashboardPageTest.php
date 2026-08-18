<?php

use App\Models\AccessToken;
use App\Models\Card;
use App\Models\Deck;
use Livewire\Livewire;

beforeEach(function () {
    $this->token = AccessToken::factory()->create();
    $this->deck = Deck::factory()->create(['access_token_id' => $this->token->id, 'name' => 'Koine Greek']);

    session(['access_token_id' => $this->token->id]);
});

test('guests are redirected away from the dashboard', function () {
    session()->flush();

    $this->get('/dashboard')->assertRedirect(route('home'));
});

test('it renders the reinforce count and recent deck', function () {
    Card::factory()->struggling()->count(2)->create(['deck_id' => $this->deck->id]);

    Livewire::test('pages::dashboard')
        ->assertSee('Você tem 2 cartões para reforçar.')
        ->assertSee('Koine Greek')
        ->assertSee('2 para reforçar');
});

test('with nothing to reinforce, the hero shows the up-to-date message', function () {
    Livewire::test('pages::dashboard')
        ->assertSee('Tudo em dia.');
});

test('switching the range recomputes the activity chart', function () {
    Livewire::test('pages::dashboard')
        ->assertSet('range', '7d')
        ->set('range', '30d')
        ->assertSet('range', '30d')
        ->assertSee('S1');
});
