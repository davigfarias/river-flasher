<?php

use App\Enums\Language;
use App\Models\AccessToken;
use App\Models\Card;
use App\Models\Deck;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;

beforeEach(function () {
    $this->token = AccessToken::factory()->create();
    $this->deck = Deck::factory()->create(['access_token_id' => $this->token->id, 'name' => 'Koine Greek']);

    session(['access_token_id' => $this->token->id]);
});

test('it shows every card with its full content', function () {
    Card::factory()->create([
        'deck_id' => $this->deck->id,
        'language' => Language::Greek,
        'pos' => 'Noun',
        'word' => 'ἀγάπη',
        'transliteration' => 'agápē',
        'definition' => 'Selfless, unconditional love.',
        'example' => 'ἡ ἀγάπη μακροθυμεῖ.',
        'translation' => 'Love is patient.',
    ]);

    Livewire::test('pages::deck-show', ['deck' => $this->deck->uuid])
        ->assertSee('ἀγάπη')
        ->assertSee('agápē')
        ->assertSee('Noun')
        ->assertSee('Selfless, unconditional love.')
        ->assertSee('ἡ ἀγάπη μακροθυμεῖ.')
        ->assertSee('Love is patient.');
});

test('a deck uuid belonging to another token 404s', function () {
    $otherToken = AccessToken::factory()->create();
    $otherDeck = Deck::factory()->create(['access_token_id' => $otherToken->id]);

    $this->get('/decks/'.$otherDeck->uuid)->assertNotFound();
});

test('a malformed uuid 404s', function () {
    $this->get('/decks/not-a-uuid')->assertNotFound();
});

test('it renders hebrew cards with rtl direction and the hebrew font', function () {
    Card::factory()->create([
        'deck_id' => $this->deck->id,
        'language' => Language::Hebrew,
        'word' => 'שָׁלוֹם',
    ]);

    Livewire::test('pages::deck-show', ['deck' => $this->deck->uuid])
        ->assertSee('dir="rtl"', false)
        ->assertSee('font-hebrew', false);
});

test('updating the name persists and dispatches a success toast', function () {
    Livewire::test('pages::deck-show', ['deck' => $this->deck->uuid])
        ->set('form.name', 'Renamed Deck')
        ->call('updateName')
        ->assertDispatched('toast-show')
        ->assertDispatched('deck-name-updated');

    expect($this->deck->fresh()->name)->toBe('Renamed Deck');
});

test('a blank name is rejected', function () {
    Livewire::test('pages::deck-show', ['deck' => $this->deck->uuid])
        ->set('form.name', '')
        ->call('updateName')
        ->assertHasErrors(['form.name' => 'required']);

    expect($this->deck->fresh()->name)->toBe('Koine Greek');
});

test('the empty state appears for a deck without cards', function () {
    Livewire::test('pages::deck-show', ['deck' => $this->deck->uuid])
        ->assertSee('Este baralho ainda não tem cartões.');
});

test('toggling deactivates and reactivates a card', function () {
    $card = Card::factory()->create(['deck_id' => $this->deck->id, 'is_active' => true]);

    Livewire::test('pages::deck-show', ['deck' => $this->deck->uuid])
        ->call('toggleCardActive', $card->id);

    expect($card->fresh()->is_active)->toBeFalse();

    Livewire::test('pages::deck-show', ['deck' => $this->deck->uuid])
        ->call('toggleCardActive', $card->id);

    expect($card->fresh()->is_active)->toBeTrue();
});

test('a card belonging to another token cannot be toggled', function () {
    $otherToken = AccessToken::factory()->create();
    $otherDeck = Deck::factory()->create(['access_token_id' => $otherToken->id]);
    $otherCard = Card::factory()->create(['deck_id' => $otherDeck->id]);

    Livewire::test('pages::deck-show', ['deck' => $this->deck->uuid])
        ->call('toggleCardActive', $otherCard->id);
})->throws(ModelNotFoundException::class);

test('deactivated cards are hidden entirely by default', function () {
    Card::factory()->create(['deck_id' => $this->deck->id, 'word' => 'visível', 'is_active' => true]);
    Card::factory()->inactive()->create(['deck_id' => $this->deck->id, 'word' => 'escondido']);

    Livewire::test('pages::deck-show', ['deck' => $this->deck->uuid])
        ->assertSee('visível')
        ->assertDontSee('escondido')
        ->assertSee('Mostrar 1 cartão desativado');
});

test('the show-inactive toggle reveals hidden cards again', function () {
    Card::factory()->inactive()->create(['deck_id' => $this->deck->id, 'word' => 'escondido']);

    Livewire::test('pages::deck-show', ['deck' => $this->deck->uuid])
        ->assertDontSee('escondido')
        ->set('showInactive', true)
        ->assertSee('escondido')
        ->assertSee('Desativado');
});

test('the empty state explains when every card is hidden rather than missing', function () {
    Card::factory()->inactive()->create(['deck_id' => $this->deck->id]);

    Livewire::test('pages::deck-show', ['deck' => $this->deck->uuid])
        ->assertSee('Todos os cartões deste baralho estão desativados.')
        ->assertDontSee('Este baralho ainda não tem cartões.');
});

test('deactivating a selection of cards in one go hides all of them and leaves the rest untouched', function () {
    $selected = Card::factory()->count(3)->create(['deck_id' => $this->deck->id, 'is_active' => true]);
    $untouched = Card::factory()->create(['deck_id' => $this->deck->id, 'is_active' => true]);

    Livewire::test('pages::deck-show', ['deck' => $this->deck->uuid])
        ->set('selectedCardIds', $selected->pluck('id')->all())
        ->call('deactivateSelected')
        ->assertDispatched('toast-show')
        ->assertSet('selectedCardIds', []);

    expect($selected->fresh()->pluck('is_active')->all())->toBe([false, false, false])
        ->and($untouched->fresh()->is_active)->toBeTrue();
});

test('activating a selection of cards in one go reveals all of them', function () {
    $selected = Card::factory()->inactive()->count(3)->create(['deck_id' => $this->deck->id]);

    Livewire::test('pages::deck-show', ['deck' => $this->deck->uuid])
        ->set('showInactive', true)
        ->set('selectedCardIds', $selected->pluck('id')->all())
        ->call('activateSelected')
        ->assertDispatched('toast-show')
        ->assertSet('selectedCardIds', []);

    expect($selected->fresh()->pluck('is_active')->all())->toBe([true, true, true]);
});

test('bulk actions ignore card ids that do not belong to the token', function () {
    $own = Card::factory()->create(['deck_id' => $this->deck->id, 'is_active' => true]);

    $otherToken = AccessToken::factory()->create();
    $otherDeck = Deck::factory()->create(['access_token_id' => $otherToken->id]);
    $otherCard = Card::factory()->create(['deck_id' => $otherDeck->id, 'is_active' => true]);

    Livewire::test('pages::deck-show', ['deck' => $this->deck->uuid])
        ->set('selectedCardIds', [$own->id, $otherCard->id])
        ->call('deactivateSelected');

    expect($own->fresh()->is_active)->toBeFalse()
        ->and($otherCard->fresh()->is_active)->toBeTrue();
});
