<?php

use App\Actions\DeleteDeck;
use App\Models\AccessToken;
use App\Models\Card;
use App\Models\Deck;
use App\Models\Review;
use App\Models\Sentence;
use App\Models\TranslationExercise;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->token = AccessToken::factory()->create();
    $this->deck = Deck::factory()->create(['access_token_id' => $this->token->id]);

    session(['access_token_id' => $this->token->id]);
});

test('it deletes the deck, its cards, their reviews/translation exercises, and the cards\' image files', function () {
    Storage::fake('public');

    $card = Card::factory()->withImage()->create(['deck_id' => $this->deck->id]);
    Storage::disk('public')->put($card->image_path, 'contents');

    $review = Review::factory()->create(['card_id' => $card->id, 'access_token_id' => $this->token->id]);
    $exercise = TranslationExercise::factory()->create(['card_id' => $card->id, 'access_token_id' => $this->token->id]);

    app(DeleteDeck::class)->handle($this->deck);

    expect(Deck::find($this->deck->id))->toBeNull()
        ->and(Card::find($card->id))->toBeNull()
        ->and(Review::find($review->id))->toBeNull()
        ->and(TranslationExercise::find($exercise->id))->toBeNull();

    Storage::disk('public')->assertMissing($card->image_path);
});

test('sentences belonging to the deck survive, unlinked rather than deleted', function () {
    $sentence = Sentence::factory()->create(['deck_id' => $this->deck->id, 'access_token_id' => $this->token->id]);

    app(DeleteDeck::class)->handle($this->deck);

    expect($sentence->fresh()->deck_id)->toBeNull();
});

test('the deck-show delete flow closes the modal, toasts and redirects to the decks list', function () {
    Card::factory()->count(2)->create(['deck_id' => $this->deck->id]);

    Livewire::test('pages::deck-show', ['deck' => $this->deck->uuid])
        ->call('deleteDeck')
        ->assertDispatched('toast-show')
        ->assertRedirect(route('decks'));

    expect(Deck::find($this->deck->id))->toBeNull();
});
