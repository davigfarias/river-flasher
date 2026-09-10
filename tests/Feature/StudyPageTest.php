<?php

use App\Models\AccessToken;
use App\Models\Card;
use App\Models\Deck;
use App\Models\Review;
use Livewire\Livewire;

beforeEach(function () {
    $this->token = AccessToken::factory()->create();
    $this->deck = Deck::factory()->create(['access_token_id' => $this->token->id]);

    session(['access_token_id' => $this->token->id]);
});

test('a deck uuid belonging to another token 404s', function () {
    $otherToken = AccessToken::factory()->create();
    $otherDeck = Deck::factory()->create(['access_token_id' => $otherToken->id]);

    $this->get('/study/'.$otherDeck->uuid)->assertNotFound();
});

test('answering "lembrei" persists the new counters and advances the session', function () {
    $card = Card::factory()->create(['deck_id' => $this->deck->id, 'aced_count' => 1, 'missed_count' => 0]);

    Livewire::test('pages::study', ['deck' => $this->deck->uuid])
        ->call('reveal')
        ->call('answer', 'remembered')
        ->call('advance')
        ->assertSet('index', 1);

    $card->refresh();

    expect($card->aced_count)->toBe(2)
        ->and($card->missed_count)->toBe(0)
        ->and(Review::where('card_id', $card->id)->exists())->toBeTrue();
});

test('answering "não lembrei" requeues the card within the same session', function () {
    $card = Card::factory()->create(['deck_id' => $this->deck->id]);

    $component = Livewire::test('pages::study', ['deck' => $this->deck->uuid])
        ->call('reveal')
        ->call('answer', 'forgot')
        ->call('advance');

    expect($component->get('cardIds'))->toBe([$card->id, $card->id])
        ->and($component->get('index'))->toBe(1);
});

test('answering "lembrei" does not requeue the card', function () {
    $card = Card::factory()->create(['deck_id' => $this->deck->id]);

    $component = Livewire::test('pages::study', ['deck' => $this->deck->uuid])
        ->call('reveal')
        ->call('answer', 'remembered');

    expect($component->get('cardIds'))->toBe([$card->id]);
});

test('the two answer buttons are shown once revealed', function () {
    Card::factory()->create(['deck_id' => $this->deck->id]);

    Livewire::test('pages::study', ['deck' => $this->deck->uuid])
        ->call('reveal')
        ->assertSee('Não lembrei')
        ->assertSee('Lembrei');
});

test('a card with an image shows it on the front face', function () {
    $card = Card::factory()->withImage()->create(['deck_id' => $this->deck->id]);

    Livewire::test('pages::study', ['deck' => $this->deck->uuid])
        ->assertSee($card->imageUrl(), false);
});

test('the card back leads with the definition and skips null fields cleanly', function () {
    Card::factory()->create([
        'deck_id' => $this->deck->id,
        'definition' => 'The first letter of the Hebrew alphabet.',
        'transliteration' => null,
        'example' => null,
        'translation' => null,
    ]);

    Livewire::test('pages::study', ['deck' => $this->deck->uuid])
        ->call('reveal')
        ->assertSee('The first letter of the Hebrew alphabet.')
        ->assertDontSee('>//<', false)
        ->assertDontSee('>""<', false);
});

test('an empty deck shows the session-complete state', function () {
    Livewire::test('pages::study', ['deck' => $this->deck->uuid])
        ->assertSee('Sessão concluída')
        ->assertSet('progress', 100);
});

test('restart reloads the queue for the same deck', function () {
    $card = Card::factory()->create(['deck_id' => $this->deck->id]);

    $component = Livewire::test('pages::study', ['deck' => $this->deck->uuid])
        ->call('reveal')
        ->call('answer', 'remembered')
        ->call('advance')
        ->assertSet('index', 1);

    $component->call('restart')->assertSet('index', 0);

    // There's no "due" concept anymore, so every card in the deck is
    // always eligible — restarting reloads the same card.
    expect($component->get('cardIds'))->toBe([$card->id]);
});

test('repeated requeues of a single card do not inflate the completion count', function () {
    $card = Card::factory()->create(['deck_id' => $this->deck->id]);

    $component = Livewire::test('pages::study', ['deck' => $this->deck->uuid])
        ->assertSet('totalCards', 1)
        ->call('reveal')->call('answer', 'forgot')->call('advance')
        ->call('reveal')->call('answer', 'forgot')->call('advance')
        ->call('reveal')->call('answer', 'remembered')->call('advance');

    $component->assertSet('totalCards', 1)
        ->assertSet('completedCount', 1)
        ->assertSet('progress', 100)
        ->assertSee('Você revisou o único cartão deste treino.');

    expect($card->fresh()->missed_count)->toBe(2)
        ->and($card->fresh()->aced_count)->toBe(1);
});

test('progress reflects distinct cards completed, not the growing requeue count', function () {
    Card::factory()->create(['deck_id' => $this->deck->id]);

    Livewire::test('pages::study', ['deck' => $this->deck->uuid])
        ->call('reveal')
        ->call('answer', 'forgot') // requeues -> still 0 of 1 completed
        ->call('advance')
        ->assertSet('progress', 0)
        ->call('reveal')
        ->call('answer', 'remembered') // completes -> 1 of 1
        ->call('advance')
        ->assertSet('progress', 100);
});

test('answer grades the card and flips it back without advancing; advance moves to the next card', function () {
    $card = Card::factory()->create(['deck_id' => $this->deck->id]);
    Card::factory()->create(['deck_id' => $this->deck->id]);

    $component = Livewire::test('pages::study', ['deck' => $this->deck->uuid])
        ->call('reveal')
        ->assertSet('revealed', true)
        ->call('answer', 'remembered')
        ->assertSet('revealed', false)
        ->assertSet('index', 0)
        ->assertSet('card.id', $card->id);

    $component->call('advance')->assertSet('index', 1);

    expect($card->fresh()->aced_count)->toBe(1);
});

test('studying with no deck param pulls cards across all of the token\'s decks, most missed first', function () {
    $otherDeck = Deck::factory()->create(['access_token_id' => $this->token->id]);
    $cardA = Card::factory()->create(['deck_id' => $this->deck->id, 'missed_count' => 1]);
    $cardB = Card::factory()->create(['deck_id' => $otherDeck->id, 'missed_count' => 0]);

    Livewire::test('pages::study')
        ->assertSet('cardIds', [$cardA->id, $cardB->id])
        ->assertSet('deckName', 'Todos os baralhos');
});

test('a decks query param builds a custom session combining just those decks', function () {
    $otherDeck = Deck::factory()->create(['access_token_id' => $this->token->id]);
    $excludedDeck = Deck::factory()->create(['access_token_id' => $this->token->id]);

    Card::factory()->create(['deck_id' => $this->deck->id, 'word' => 'included-one']);
    Card::factory()->create(['deck_id' => $otherDeck->id, 'word' => 'included-two']);
    Card::factory()->create(['deck_id' => $excludedDeck->id, 'word' => 'excluded-three']);

    $this->get('/study?decks='.$this->deck->uuid.','.$otherDeck->uuid)
        ->assertOk()
        ->assertSee('2 baralhos selecionados');
});

test('goBack undoes a "lembrei" answer, restores counters, and re-shows the card revealed', function () {
    $card = Card::factory()->create(['deck_id' => $this->deck->id, 'aced_count' => 1, 'missed_count' => 0]);

    $component = Livewire::test('pages::study', ['deck' => $this->deck->uuid])
        ->call('reveal')
        ->call('answer', 'remembered')
        ->call('advance')
        ->assertSet('completedCount', 1)
        ->assertSet('index', 1);

    $component->call('goBack')
        ->assertSet('index', 0)
        ->assertSet('revealed', true)
        ->assertSet('completedCount', 0)
        ->assertSet('card.id', $card->id);

    $card->refresh();

    expect($card->aced_count)->toBe(1)
        ->and($card->missed_count)->toBe(0)
        ->and(Review::where('card_id', $card->id)->exists())->toBeFalse();
});

test('goBack undoes a "não lembrei" answer and removes the requeued duplicate', function () {
    $card = Card::factory()->create(['deck_id' => $this->deck->id]);

    $component = Livewire::test('pages::study', ['deck' => $this->deck->uuid])
        ->call('reveal')
        ->call('answer', 'forgot')
        ->call('advance');

    expect($component->get('cardIds'))->toBe([$card->id, $card->id]);

    $component->call('goBack')
        ->assertSet('index', 0)
        ->assertSet('revealed', true);

    expect($component->get('cardIds'))->toBe([$card->id])
        ->and($card->fresh()->missed_count)->toBe(0)
        ->and(Review::where('card_id', $card->id)->exists())->toBeFalse();
});

test('goBack does nothing when there is no history yet', function () {
    Card::factory()->create(['deck_id' => $this->deck->id]);

    Livewire::test('pages::study', ['deck' => $this->deck->uuid])
        ->assertSet('canGoBack', false)
        ->call('goBack')
        ->assertSet('index', 0)
        ->assertSet('revealed', false);
});

test('the back arrow is disabled until an answer has been given', function () {
    Card::factory()->create(['deck_id' => $this->deck->id]);

    Livewire::test('pages::study', ['deck' => $this->deck->uuid])
        ->assertSet('canGoBack', false)
        ->call('reveal')
        ->call('answer', 'remembered')
        ->assertSet('canGoBack', true);
});

test('a foreign or bogus uuid in the decks query param is dropped rather than 404ing the session', function () {
    $otherToken = AccessToken::factory()->create();
    $foreignDeck = Deck::factory()->create(['access_token_id' => $otherToken->id]);

    Card::factory()->create(['deck_id' => $this->deck->id]);

    $this->get('/study?decks='.$this->deck->uuid.','.$foreignDeck->uuid.',not-a-real-uuid')
        ->assertOk()
        ->assertSee($this->deck->name);
});
