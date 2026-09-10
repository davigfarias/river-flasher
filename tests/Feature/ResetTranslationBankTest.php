<?php

use App\Models\AccessToken;
use App\Models\Card;
use App\Models\Deck;
use App\Models\TranslationExercise;
use Livewire\Livewire;

beforeEach(function () {
    $this->token = AccessToken::factory()->create();
    $this->deck = Deck::factory()->create(['access_token_id' => $this->token->id]);

    session(['access_token_id' => $this->token->id]);
});

test('resetting clears this deck\'s cached exercises only', function () {
    $card = Card::factory()->withSentence()->create(['deck_id' => $this->deck->id]);
    TranslationExercise::factory()->create(['card_id' => $card->id, 'access_token_id' => $this->token->id]);

    $otherDeck = Deck::factory()->create(['access_token_id' => $this->token->id]);
    $otherCard = Card::factory()->withSentence()->create(['deck_id' => $otherDeck->id]);
    TranslationExercise::factory()->create(['card_id' => $otherCard->id, 'access_token_id' => $this->token->id]);

    Livewire::test('pages::deck-show', ['deck' => $this->deck->uuid])
        ->assertSee('Resetar banco de frases')
        ->call('resetTranslationBank')
        ->assertDispatched('toast-show');

    expect(TranslationExercise::where('card_id', $card->id)->exists())->toBeFalse()
        ->and(TranslationExercise::where('card_id', $otherCard->id)->exists())->toBeTrue();
});

test('the reset button is hidden when the deck has no cached exercises', function () {
    Card::factory()->withSentence()->create(['deck_id' => $this->deck->id]);

    Livewire::test('pages::deck-show', ['deck' => $this->deck->uuid])
        ->assertDontSee('Resetar banco de frases');
});
