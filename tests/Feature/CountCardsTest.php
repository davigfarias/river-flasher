<?php

use App\Actions\CountCards;
use App\Models\AccessToken;
use App\Models\Card;
use App\Models\Deck;
use Carbon\CarbonImmutable;

test('it counts total cards and cards created this week, scoped to the token', function () {
    $token = AccessToken::factory()->create();
    $deck = Deck::factory()->create(['access_token_id' => $token->id]);
    $now = CarbonImmutable::now();

    Card::factory()->create(['deck_id' => $deck->id, 'created_at' => $now->subDays(10)]);
    Card::factory()->create(['deck_id' => $deck->id, 'created_at' => $now->subDay()]);
    Card::factory()->create(['deck_id' => $deck->id, 'created_at' => $now]);

    $otherToken = AccessToken::factory()->create();
    $otherDeck = Deck::factory()->create(['access_token_id' => $otherToken->id]);
    Card::factory()->create(['deck_id' => $otherDeck->id, 'created_at' => $now]);

    $counts = app(CountCards::class)->handle($token->id, $now);

    expect($counts['total'])->toBe(3)
        ->and($counts['thisWeek'])->toBe(2);
});
