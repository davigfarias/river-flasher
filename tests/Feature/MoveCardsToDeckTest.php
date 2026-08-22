<?php

use App\Actions\MoveCardsToDeck;
use App\Models\Card;
use App\Models\Deck;

test('it reassigns the given cards to the deck', function () {
    $origin = Deck::factory()->create();
    $target = Deck::factory()->create();

    $moved = Card::factory()->create(['deck_id' => $origin->id]);
    $untouched = Card::factory()->create(['deck_id' => $origin->id]);

    $count = app(MoveCardsToDeck::class)->handle($target, [$moved->id]);

    expect($count)->toBe(1)
        ->and($moved->fresh()->deck_id)->toBe($target->id)
        ->and($untouched->fresh()->deck_id)->toBe($origin->id);
});
