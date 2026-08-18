<?php

use App\Models\AccessToken;
use App\Models\Deck;
use Livewire\Livewire;

beforeEach(function () {
    $this->token = AccessToken::factory()->create();
    session(['access_token_id' => $this->token->id]);
});

test('it validates the required fields', function () {
    Livewire::test('new-deck-modal')
        ->set('form.name', '')
        ->call('create')
        ->assertHasErrors(['form.name' => 'required']);

    expect(Deck::count())->toBe(0);
});

test('it creates a deck scoped to the current token and resets the form', function () {
    Livewire::test('new-deck-modal')
        ->set('form.name', 'Psalms of Ascent')
        ->call('create')
        ->assertHasNoErrors()
        ->assertSet('form.name', '');

    $deck = Deck::sole();

    expect($deck->access_token_id)->toBe($this->token->id)
        ->and($deck->name)->toBe('Psalms of Ascent')
        ->and($deck->slug)->toBe('psalms-of-ascent');
});
