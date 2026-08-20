<?php

use App\Actions\UpdateDeck;
use App\DTO\DeckData;
use App\Models\Deck;

test('it renames the deck without changing its uuid', function () {
    $deck = Deck::factory()->create(['name' => 'Old Name']);
    $uuid = $deck->uuid;

    $updated = app(UpdateDeck::class)->handle($deck, new DeckData(name: 'New Name'));

    expect($updated->name)->toBe('New Name')
        ->and($updated->uuid)->toBe($uuid);
});
