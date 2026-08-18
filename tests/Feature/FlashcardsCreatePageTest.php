<?php

use App\Enums\Language;
use App\Models\AccessToken;
use App\Models\Card;
use App\Models\Deck;
use Livewire\Livewire;

beforeEach(function () {
    $this->token = AccessToken::factory()->create();
    $this->deck = Deck::factory()->create([
        'access_token_id' => $this->token->id,
    ]);

    session(['access_token_id' => $this->token->id]);
});

test('it defaults to the first deck and the form\'s default language', function () {
    Livewire::test('pages::flashcards-create')
        ->assertSet('form.deck', $this->deck->slug)
        ->assertSet('form.language', 'el');
});

test('it validates required fields', function () {
    Livewire::test('pages::flashcards-create')
        ->set('form.word', '')
        ->set('form.definition', '')
        ->call('addToDeck')
        ->assertHasErrors(['form.word' => 'required', 'form.definition' => 'required']);

    expect(Card::count())->toBe(0);
});

test('it creates a card with the rich fields and resets the form', function () {
    Livewire::test('pages::flashcards-create')
        ->set('form.word', 'χάρις')
        ->set('form.transliteration', 'cháris')
        ->set('form.pos', 'Noun')
        ->set('form.definition', 'Grace; favor freely given.')
        ->set('form.example', 'τῇ γὰρ χάριτί ἐστε σεσῳσμένοι')
        ->set('form.translation', 'For by grace you have been saved.')
        ->set('form.markDifficult', true)
        ->call('addToDeck')
        ->assertHasNoErrors()
        ->assertSet('form.word', '')
        ->assertSet('form.markDifficult', false);

    $card = Card::sole();

    expect($card->deck_id)->toBe($this->deck->id)
        ->and($card->word)->toBe('χάρις')
        ->and($card->transliteration)->toBe('cháris')
        ->and($card->pos)->toBe('Noun')
        ->and($card->definition)->toBe('Grace; favor freely given.')
        ->and($card->example)->toBe('τῇ γὰρ χάριτί ἐστε σεσῳσμένοι')
        ->and($card->translation)->toBe('For by grace you have been saved.')
        ->and($card->language)->toBe(Language::Greek)
        ->and($card->is_difficult)->toBeTrue();
});

test('switching decks does not reset the language the user picked', function () {
    $otherDeck = Deck::factory()->create(['access_token_id' => $this->token->id]);

    Livewire::test('pages::flashcards-create')
        ->set('form.language', 'he')
        ->set('form.deck', $otherDeck->slug)
        ->assertSet('form.language', 'he');
});

test('deck options are limited to the current token', function () {
    $otherToken = AccessToken::factory()->create();
    Deck::factory()->create(['access_token_id' => $otherToken->id, 'name' => 'Someone else\'s deck']);

    $component = Livewire::test('pages::flashcards-create');

    expect($component->get('decks'))->toHaveCount(1)
        ->and($component->get('decks'))->toHaveKey($this->deck->slug);
});

test('a deck slug belonging to another token is rejected on submit', function () {
    $otherToken = AccessToken::factory()->create();
    $otherDeck = Deck::factory()->create(['access_token_id' => $otherToken->id]);

    Livewire::test('pages::flashcards-create')
        ->set('form.deck', $otherDeck->slug)
        ->set('form.word', 'word')
        ->set('form.definition', 'definition')
        ->call('addToDeck')
        ->assertHasErrors(['form.deck']);

    expect(Card::count())->toBe(0);
});

test('recently added cards are shown for the selected deck', function () {
    Card::factory()->create(['deck_id' => $this->deck->id, 'word' => 'λόγος']);

    Livewire::test('pages::flashcards-create')
        ->assertSee('λόγος');
});
