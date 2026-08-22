<?php

use App\Actions\ToggleCardActive;
use App\Models\Card;

test('it flips an active card to inactive', function () {
    $card = Card::factory()->create(['is_active' => true]);

    $updated = app(ToggleCardActive::class)->handle($card);

    expect($updated->is_active)->toBeFalse()
        ->and($card->fresh()->is_active)->toBeFalse();
});

test('it flips an inactive card back to active', function () {
    $card = Card::factory()->inactive()->create();

    $updated = app(ToggleCardActive::class)->handle($card);

    expect($updated->is_active)->toBeTrue();
});
