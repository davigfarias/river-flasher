<?php

use App\Models\AccessToken;
use App\Models\Card;
use App\Models\Deck;
use Livewire\Livewire;

beforeEach(function () {
    $this->token = AccessToken::factory()->create();
    $this->deck = Deck::factory()->create(['access_token_id' => $this->token->id]);

    session(['access_token_id' => $this->token->id]);
});

test('it only lists declensions that have a card ready for tradução', function () {
    Card::factory()->declinable(paradigmSlug: 'noun-2-masc')->withSentence()->create(['deck_id' => $this->deck->id]);
    Card::factory()->declinable()->withoutSentence()->create(['deck_id' => $this->deck->id]); // noun-2-masc, but no sentence

    Livewire::test('pages::study-grammar', ['deck' => $this->deck->uuid])
        ->assertSet('options', ['noun-2-masc' => '2ª declinação masculina (-ος)'])
        ->assertSee('2ª declinação masculina');
});

test('choosing a declension moves to the theory step with its endings table', function () {
    Card::factory()->declinable(paradigmSlug: 'noun-2-masc')->withSentence()->create(['deck_id' => $this->deck->id]);

    Livewire::test('pages::study-grammar', ['deck' => $this->deck->uuid])
        ->call('choose', 'noun-2-masc')
        ->assertSet('step', 'theory')
        ->assertSee('2ª declinação masculina')
        ->assertSee('ος') // nom sg ending
        ->assertSee(route('study', ['deck' => $this->deck->uuid, 'mode' => 'translation', 'paradigm' => 'noun-2-masc']));
});

test('choosing a declension nobody offered 404s', function () {
    Livewire::test('pages::study-grammar', ['deck' => $this->deck->uuid])
        ->call('choose', 'noun-2-masc')
        ->assertNotFound();
});

test('with no eligible cards, it offers an unfiltered tradução link instead', function () {
    Livewire::test('pages::study-grammar', ['deck' => $this->deck->uuid])
        ->assertSet('options', [])
        ->assertSee(route('study', ['deck' => $this->deck->uuid, 'mode' => 'translation']), false);
});

test('it scopes the options to the given deck', function () {
    $otherDeck = Deck::factory()->create(['access_token_id' => $this->token->id]);
    Card::factory()->declinable(paradigmSlug: 'noun-1-fem-eta')->withSentence()->create(['deck_id' => $otherDeck->id]);

    Livewire::test('pages::study-grammar', ['deck' => $this->deck->uuid])
        ->assertSet('options', []);
});
