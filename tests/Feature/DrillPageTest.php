<?php

use App\Enums\SentenceStatus;
use App\Models\AccessToken;
use App\Models\Deck;
use App\Models\Sentence;
use Livewire\Livewire;

beforeEach(function () {
    $this->token = AccessToken::factory()->create();
    $this->deck = Deck::factory()->create(['access_token_id' => $this->token->id]);

    session(['access_token_id' => $this->token->id]);
});

test('a deck belonging to another token 404s', function () {
    $otherDeck = Deck::factory()->create(['access_token_id' => AccessToken::factory()->create()->id]);

    $this->get(route('drills.show', $otherDeck))->assertNotFound();
});

test('it shows the empty state when the deck has no approved sentences', function () {
    Livewire::test('pages::drill', ['deck' => $this->deck->uuid])
        ->assertSet('item', null)
        ->assertSee('Nenhuma frase aprovada');
});

test('choosing the right form marks it correct and reveals the analysis', function () {
    seedDrillSentence($this->deck);

    Livewire::test('pages::drill', ['deck' => $this->deck->uuid])
        ->call('choose', 'ανθρωπῳ')
        ->assertSet('answered', true)
        ->assertSet('lastCorrect', true)
        ->assertSee('dativo singular');
});

test('choosing a wrong form marks it incorrect and records the miss', function () {
    seedDrillSentence($this->deck);

    Livewire::test('pages::drill', ['deck' => $this->deck->uuid])
        ->call('choose', 'ανθρωπον')
        ->assertSet('lastCorrect', false)
        ->assertSet('misses', ['dativo singular'])
        ->assertSee('Não é essa.');
});

test('a full session ends with the score and the missed traits', function () {
    seedDrillSentence($this->deck);

    $component = Livewire::test('pages::drill', ['deck' => $this->deck->uuid]);

    for ($i = 0; $i < 12; $i++) {
        $answer = $i % 2 === 0 ? 'ανθρωπῳ' : 'ανθρωπον';
        $component->call('choose', $answer)->call('next');
    }

    $component->assertSet('finished', true)
        ->assertSee('Sessão concluída')
        ->assertSee('6 de 12')
        ->assertSee('dativo singular');
});

test('the session ends early when the sentence pool runs dry', function () {
    seedDrillSentence($this->deck);

    $component = Livewire::test('pages::drill', ['deck' => $this->deck->uuid])
        ->call('choose', 'ανθρωπῳ')
        ->call('next');

    // Only one sentence existed; deleting its approval empties the pool.
    Sentence::query()->update(['status' => SentenceStatus::Rejected]);

    $component->call('choose', 'ανθρωπῳ')->call('next')
        ->assertSet('finished', true)
        ->assertSee('Sessão concluída');
});

test('restart clears the score and pulls a fresh item', function () {
    seedDrillSentence($this->deck);

    Livewire::test('pages::drill', ['deck' => $this->deck->uuid])
        ->call('choose', 'ανθρωπον')
        ->call('next')
        ->assertSet('completed', 1)
        ->call('restart')
        ->assertSet('completed', 0)
        ->assertSet('misses', [])
        ->assertSet('answered', false);
});
