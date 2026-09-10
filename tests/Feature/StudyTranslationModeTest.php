<?php

use App\Enums\StudyMode;
use App\Models\AccessToken;
use App\Models\Card;
use App\Models\Deck;
use App\Models\Review;
use App\Models\TranslationExercise;
use Livewire\Livewire;

beforeEach(function () {
    $this->token = AccessToken::factory()->create();
    $this->deck = Deck::factory()->create(['access_token_id' => $this->token->id]);

    session(['access_token_id' => $this->token->id]);
});

function translationDeckCard(Deck $deck, string $example = 'ἡ ἀγάπη μακροθυμεῖ.', string $translation = 'O amor é paciente'): Card
{
    // A few siblings so the word bank has somewhere to draw distractors from.
    // They carry no example sentence, so they stay out of the session itself.
    Card::factory()->count(4)->create([
        'deck_id' => $deck->id,
        'example' => null,
        'translation' => 'A esperança nunca falha jamais',
    ]);

    return Card::factory()->create([
        'deck_id' => $deck->id,
        'example' => $example,
        'translation' => $translation,
    ]);
}

test('cards without an example sentence are left out of the translation session', function () {
    Card::factory()->withoutSentence()->create(['deck_id' => $this->deck->id]);

    Livewire::test('pages::study', ['deck' => $this->deck->uuid, 'mode' => 'translation'])
        ->assertSet('totalCards', 0)
        ->assertSee('Sessão concluída');
});

test('the exercise is parsed and cached from the card, and the sentence is shown', function () {
    $card = translationDeckCard($this->deck);

    Livewire::test('pages::study', ['deck' => $this->deck->uuid, 'mode' => 'translation'])
        ->assertSet('totalCards', 1)
        ->assertSee('ἡ ἀγάπη μακροθυμεῖ.')
        ->assertSee('Verificar');

    expect(TranslationExercise::where('card_id', $card->id)->sole()->tokens)
        ->toBe(['O', 'amor', 'é', 'paciente']);
});

test('a correct assembly grades onto the translation counters and records the mode', function () {
    $card = translationDeckCard($this->deck);

    Livewire::test('pages::study', ['deck' => $this->deck->uuid, 'mode' => 'translation'])
        ->call('checkTranslation', ['O', 'amor', 'é', 'paciente'])
        ->assertSet('translationCorrect', true)
        ->assertSet('translationResolved', true);

    $card->refresh();

    expect($card->translation_aced_count)->toBe(1)
        ->and($card->translation_missed_count)->toBe(0)
        ->and($card->aced_count)->toBe(0);

    expect(Review::where('card_id', $card->id)->sole()->mode)->toBe(StudyMode::Translation);
});

test('the first wrong attempt is a free retry; the second grades the card as missed', function () {
    $card = translationDeckCard($this->deck);

    $component = Livewire::test('pages::study', ['deck' => $this->deck->uuid, 'mode' => 'translation'])
        ->call('checkTranslation', ['amor', 'O', 'é', 'paciente'])
        ->assertSet('translationAttempt', 1)
        ->assertSet('translationResolved', false);

    expect(Review::where('card_id', $card->id)->exists())->toBeFalse();

    $component->call('checkTranslation', ['amor', 'O', 'é', 'paciente'])
        ->assertSet('translationResolved', true)
        ->assertSet('translationCorrect', false)
        ->assertSee('Resposta certa:');

    expect($card->fresh()->translation_missed_count)->toBe(1);

    // Missed cards are requeued within the session, like "não lembrei".
    expect($component->get('cardIds'))->toBe([$card->id, $card->id]);
});

test('advancing clears the per-card translation state', function () {
    translationDeckCard($this->deck);

    Livewire::test('pages::study', ['deck' => $this->deck->uuid, 'mode' => 'translation'])
        ->call('checkTranslation', ['O', 'amor', 'é', 'paciente'])
        ->call('advance')
        ->assertSet('translationAttempt', 0)
        ->assertSet('translationCorrect', null)
        ->assertSet('translationResolved', false);
});
