<?php

use App\Models\AccessToken;
use App\Models\Card;
use App\Models\Deck;
use App\Models\Review;
use Livewire\Livewire;

beforeEach(function () {
    $this->token = AccessToken::factory()->create();
    $this->deck = Deck::factory()->create(['access_token_id' => $this->token->id, 'slug' => 'greek-nt-vocab']);

    session(['access_token_id' => $this->token->id]);
});

test('it only loads cards that are currently due', function () {
    $due = Card::factory()->dueNow()->create(['deck_id' => $this->deck->id]);
    Card::factory()->create(['deck_id' => $this->deck->id, 'due_at' => now()->addDay()]);

    Livewire::test('pages::study', ['deck' => 'greek-nt-vocab'])
        ->assertSet('cardIds', [$due->id]);
});

test('a deck slug belonging to another token 404s', function () {
    $otherToken = AccessToken::factory()->create();
    $otherDeck = Deck::factory()->create(['access_token_id' => $otherToken->id, 'slug' => 'not-yours']);

    $this->get('/study/'.$otherDeck->slug)->assertNotFound();
});

test('rating a card persists its new SRS state and advances the session', function () {
    $card = Card::factory()->dueNow()->create(['deck_id' => $this->deck->id, 'interval_minutes' => 1_440, 'ease_factor' => 2.5]);

    Livewire::test('pages::study', ['deck' => 'greek-nt-vocab'])
        ->call('reveal')
        ->call('rate', 'good')
        ->assertSet('index', 1);

    $card->refresh();

    expect($card->interval_minutes)->toBe(3_600)
        ->and(Review::where('card_id', $card->id)->exists())->toBeTrue();
});

test('a short learning-step rating requeues the card within the same session', function () {
    $card = Card::factory()->dueNow()->create(['deck_id' => $this->deck->id, 'interval_minutes' => 0]);

    $component = Livewire::test('pages::study', ['deck' => 'greek-nt-vocab'])
        ->call('reveal')
        ->call('rate', 'good'); // new card + Good -> 10m, which requeues

    expect($component->get('cardIds'))->toBe([$card->id, $card->id])
        ->and($component->get('index'))->toBe(1);
});

test('a mature-interval rating does not requeue the card', function () {
    $card = Card::factory()->dueNow()->create(['deck_id' => $this->deck->id, 'interval_minutes' => 1_440, 'ease_factor' => 2.5]);

    $component = Livewire::test('pages::study', ['deck' => 'greek-nt-vocab'])
        ->call('reveal')
        ->call('rate', 'good');

    expect($component->get('cardIds'))->toBe([$card->id]);
});

test('the rating buttons show real computed interval labels', function () {
    Card::factory()->dueNow()->create(['deck_id' => $this->deck->id, 'interval_minutes' => 0]);

    Livewire::test('pages::study', ['deck' => 'greek-nt-vocab'])
        ->call('reveal')
        ->assertSee('Again (1m)')
        ->assertSee('Hard (6m)')
        ->assertSee('Good (10m)')
        ->assertSee('Easy (4d)');
});

test('an empty due queue shows the session-complete state', function () {
    Livewire::test('pages::study', ['deck' => 'greek-nt-vocab'])
        ->assertSee('Session complete')
        ->assertSet('progress', 100);
});

test('restart reloads a fresh due queue for the same deck', function () {
    $card = Card::factory()->dueNow()->create(['deck_id' => $this->deck->id]);

    $component = Livewire::test('pages::study', ['deck' => 'greek-nt-vocab'])
        ->call('reveal')
        ->call('rate', 'easy'); // graduates far into the future

    // The queue itself doesn't shrink as cards are rated — `index` just
    // walks past them. Past the end, `card` is null (session complete).
    expect($component->get('cardIds'))->toBe([$card->id])
        ->and($component->get('index'))->toBe(1);

    $component->call('restart')->assertSet('index', 0);

    // The just-reviewed card is no longer due, so the fresh queue is empty.
    expect($component->get('cardIds'))->toBe([])
        ->and($card->fresh()->interval_minutes)->toBeGreaterThan(0);
});

test('repeated requeues of a single card do not inflate the completion count', function () {
    $card = Card::factory()->dueNow()->create(['deck_id' => $this->deck->id, 'interval_minutes' => 0]);

    $component = Livewire::test('pages::study', ['deck' => 'greek-nt-vocab'])
        ->assertSet('totalCards', 1)
        ->call('reveal')->call('rate', 'again') // 1m, requeues
        ->call('reveal')->call('rate', 'hard') // 6m, requeues
        ->call('reveal')->call('rate', 'easy'); // graduates, session ends

    $component->assertSet('totalCards', 1)
        ->assertSet('completedCount', 1)
        ->assertSet('progress', 100)
        ->assertSee('You reviewed all 1 card in this deck.');

    expect($card->fresh()->interval_minutes)->toBeGreaterThanOrEqual(15);
});

test('progress reflects distinct cards completed, not the growing requeue count', function () {
    $card = Card::factory()->dueNow()->create(['deck_id' => $this->deck->id, 'interval_minutes' => 0]);

    Livewire::test('pages::study', ['deck' => 'greek-nt-vocab'])
        ->call('reveal')
        ->call('rate', 'good') // 10m, requeues -> still 0 of 1 completed
        ->assertSet('progress', 0)
        ->call('reveal')
        ->call('rate', 'easy') // graduates -> 1 of 1 completed
        ->assertSet('progress', 100);
});

test('studying with no deck param pulls due cards across all of the token\'s decks', function () {
    $otherDeck = Deck::factory()->create(['access_token_id' => $this->token->id]);
    $cardA = Card::factory()->create(['deck_id' => $this->deck->id, 'due_at' => now()->subMinutes(2)]);
    $cardB = Card::factory()->create(['deck_id' => $otherDeck->id, 'due_at' => now()->subMinute()]);

    Livewire::test('pages::study')
        ->assertSet('cardIds', [$cardA->id, $cardB->id])
        ->assertSet('deckName', 'All decks');
});
