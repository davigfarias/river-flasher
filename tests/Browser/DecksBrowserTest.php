<?php

use App\Enums\Language;
use App\Models\AccessToken;
use App\Models\Card;
use App\Models\Deck;

beforeEach(function () {
    $this->token = AccessToken::factory()->create(['token' => hash('sha256', '1234')]);
});

test('language badges are visible on the decks listing', function () {
    $greekDeck = Deck::factory()->create(['access_token_id' => $this->token->id, 'name' => 'Grego Test Deck']);
    Card::factory()->create(['deck_id' => $greekDeck->id, 'language' => Language::Greek]);

    $hebrewDeck = Deck::factory()->create(['access_token_id' => $this->token->id, 'name' => 'Hebraico Test Deck']);
    Card::factory()->create(['deck_id' => $hebrewDeck->id, 'language' => Language::Hebrew]);

    $page = loginWithCode(visit('/'), '1234');

    $page->assertPathIs('/dashboard');

    $page->navigate('/decks');

    $page->assertSee('Grego Test Deck')
        ->assertSee('Hebraico Test Deck')
        ->assertSee('Grego')
        ->assertSee('Hebraico');
});
