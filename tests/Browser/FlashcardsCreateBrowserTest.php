<?php

use App\Enums\Language;
use App\Models\AccessToken;
use App\Models\Card;
use App\Models\Deck;

beforeEach(function () {
    $this->token = AccessToken::factory()->create(['token' => hash('sha256', '1234')]);
    $this->deck = Deck::factory()->create(['access_token_id' => $this->token->id, 'name' => 'Koine Greek']);
    Card::factory()->create(['deck_id' => $this->deck->id, 'language' => Language::Greek]);
});

test('submitting a card in the wrong language shows a danger toast and keeps the card unsaved', function () {
    $page = loginWithCode(visit('/'), '1234');
    $page->assertPathIs('/dashboard');
    $page->navigate('/flashcards/create');

    $page->click('Hebraico')
        ->type('input[wire\\:model="form.word"]', 'שָׁלוֹם')
        ->type('textarea[wire\\:model="form.definition"]', 'Peace.')
        ->click('Adicionar ao baralho')
        ->assertSee('Idioma diferente do baralho');

    expect(Card::count())->toBe(1);
});
