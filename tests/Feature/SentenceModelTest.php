<?php

use App\Enums\Gender;
use App\Enums\SentenceStatus;
use App\Models\Card;
use App\Models\Deck;
use App\Models\Sentence;
use App\Models\SentenceToken;

test('a sentence casts its enums and orders its tokens by position', function () {
    $sentence = Sentence::factory()->create(['status' => SentenceStatus::Pending]);

    SentenceToken::factory()->for($sentence)->create(['position' => 2, 'surface' => 'γ']);
    SentenceToken::factory()->for($sentence)->create(['position' => 0, 'surface' => 'α']);
    SentenceToken::factory()->for($sentence)->create(['position' => 1, 'surface' => 'β']);

    expect($sentence->status)->toBe(SentenceStatus::Pending)
        ->and($sentence->tokens->pluck('surface')->all())->toBe(['α', 'β', 'γ']);
});

test('pending and approved scopes filter by status', function () {
    Sentence::factory()->count(2)->create();
    Sentence::factory()->approved()->create();

    expect(Sentence::pending()->count())->toBe(2)
        ->and(Sentence::approved()->count())->toBe(1);
});

test('deleting a sentence cascades its tokens but only nulls the card link', function () {
    $card = Card::factory()->create();
    $sentence = Sentence::factory()->create();
    $token = SentenceToken::factory()->for($sentence)->create(['card_id' => $card->id]);

    $sentence->delete();

    expect(SentenceToken::find($token->id))->toBeNull()
        ->and(Card::find($card->id))->not->toBeNull();
});

test('a card stores morphology columns and the declinable scope needs both stem and paradigm', function () {
    $deck = Deck::factory()->create();

    $ready = Card::factory()->declinable('λογ', 'noun-2-masc', Gender::Masculine)->create(['deck_id' => $deck->id]);
    Card::factory()->create(['deck_id' => $deck->id, 'stem' => 'σαρκ', 'paradigm_slug' => null]);

    expect($ready->fresh()->gender)->toBe(Gender::Masculine)
        ->and($deck->cards()->declinable()->pluck('id')->all())->toBe([$ready->id]);
});
