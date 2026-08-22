<?php

use App\Actions\SetCardsActiveState;
use App\Models\Card;

test('it deactivates the given cards and leaves others untouched', function () {
    $cards = Card::factory()->count(2)->create(['is_active' => true]);
    $untouched = Card::factory()->create(['is_active' => true]);

    $count = app(SetCardsActiveState::class)->handle($cards->pluck('id')->all(), false);

    expect($count)->toBe(2)
        ->and($cards->fresh()->pluck('is_active')->all())->toBe([false, false])
        ->and($untouched->fresh()->is_active)->toBeTrue();
});

test('it reactivates the given cards', function () {
    $cards = Card::factory()->inactive()->count(2)->create();

    $count = app(SetCardsActiveState::class)->handle($cards->pluck('id')->all(), true);

    expect($count)->toBe(2)
        ->and($cards->fresh()->pluck('is_active')->all())->toBe([true, true]);
});
