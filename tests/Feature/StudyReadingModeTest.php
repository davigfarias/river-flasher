<?php

use App\Enums\StudyMode;
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

test('the reading session grades onto the reading counters, leaving meaning untouched', function () {
    $card = Card::factory()->create([
        'deck_id' => $this->deck->id,
        'aced_count' => 4,
        'missed_count' => 2,
        'transliteration' => 'agápē',
    ]);

    Livewire::test('pages::study', ['deck' => $this->deck->uuid, 'mode' => 'reading'])
        ->assertSet('mode', 'reading')
        ->call('reveal')
        ->assertSee('Li certo')
        ->call('answer', 'remembered')
        ->call('advance');

    $card->refresh();

    expect($card->reading_aced_count)->toBe(1)
        ->and($card->reading_missed_count)->toBe(0)
        ->and($card->aced_count)->toBe(4)
        ->and($card->missed_count)->toBe(2);

    $review = Review::where('card_id', $card->id)->sole();

    expect($review->mode)->toBe(StudyMode::Reading);
});

test('the reveal shows the transliteration and definition', function () {
    Card::factory()->create([
        'deck_id' => $this->deck->id,
        'transliteration' => 'anthropos',
        'definition' => 'ser humano',
    ]);

    Livewire::test('pages::study', ['deck' => $this->deck->uuid, 'mode' => 'reading'])
        ->call('reveal')
        ->assertSee('anthropos')
        ->assertSee('ser humano');
});

test('goBack in reading mode restores the reading counters', function () {
    $card = Card::factory()->create(['deck_id' => $this->deck->id, 'transliteration' => 'x']);

    $component = Livewire::test('pages::study', ['deck' => $this->deck->uuid, 'mode' => 'reading'])
        ->call('reveal')
        ->call('answer', 'forgot')
        ->call('advance');

    expect($card->fresh()->reading_missed_count)->toBe(1);

    $component->call('goBack');

    expect($card->fresh()->reading_missed_count)->toBe(0)
        ->and(Review::where('card_id', $card->id)->exists())->toBeFalse();
});
