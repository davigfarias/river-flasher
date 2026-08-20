<?php

use App\Enums\Language;
use App\Models\AccessToken;
use App\Models\Card;
use App\Models\Deck;

beforeEach(function () {
    $this->token = AccessToken::factory()->create(['token' => hash('sha256', '1234')]);
    $this->deck = Deck::factory()->create(['access_token_id' => $this->token->id, 'name' => 'Koine Greek']);
});

test('the pencil opens the edit modal pre-filled', function () {
    Card::factory()->create([
        'deck_id' => $this->deck->id,
        'language' => Language::Greek,
        'word' => 'ἀγάπη',
    ]);

    $page = loginWithCode(visit('/'), '1234');
    $page->assertPathIs('/dashboard');
    $page->navigate('/decks/'.$this->deck->uuid);

    $page->click('button[title="Editar cartão"]')
        ->assertSee('Editar cartão')
        ->assertValue('input[wire\\:model="form.word"]', 'ἀγάπη');
});

test('editing a card updates the grid without leaving the page', function () {
    Card::factory()->create([
        'deck_id' => $this->deck->id,
        'language' => Language::Greek,
        'word' => 'ἀγάπη',
        'definition' => 'Love.',
    ]);

    $page = loginWithCode(visit('/'), '1234');
    $page->assertPathIs('/dashboard');
    $page->navigate('/decks/'.$this->deck->uuid);

    $page->click('button[title="Editar cartão"]')
        ->clear('textarea[wire\\:model="form.definition"]')
        ->type('textarea[wire\\:model="form.definition"]', 'Selfless, unconditional love.')
        ->click('Salvar alterações')
        ->assertSee('Cartão atualizado')
        ->assertSee('Selfless, unconditional love.');
});

test('the deck name can be edited inline', function () {
    $page = loginWithCode(visit('/'), '1234');
    $page->assertPathIs('/dashboard');
    $page->navigate('/decks/'.$this->deck->uuid);

    $page->click('button[title="Renomear baralho"]')
        ->clear('form[wire\\:submit="updateName"] input')
        ->type('form[wire\\:submit="updateName"] input', 'Koine Greek Vocabulary')
        ->click('button[title="Salvar"]')
        ->assertSee('Nome atualizado')
        ->assertSee('Koine Greek Vocabulary');
});
