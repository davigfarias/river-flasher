<?php

use App\Actions\CountCardsToReinforce;
use App\Models\AccessToken;
use App\Models\Card;
use App\Models\Deck;

test('it counts only cards with more misses than aces, scoped to the token', function () {
    $token = AccessToken::factory()->create();
    $deck = Deck::factory()->create(['access_token_id' => $token->id]);

    Card::factory()->create(['deck_id' => $deck->id, 'aced_count' => 0, 'missed_count' => 2]); // to reinforce
    Card::factory()->create(['deck_id' => $deck->id, 'aced_count' => 2, 'missed_count' => 2]); // tied, not counted
    Card::factory()->create(['deck_id' => $deck->id, 'aced_count' => 3, 'missed_count' => 1]); // ahead, not counted

    $otherToken = AccessToken::factory()->create();
    $otherDeck = Deck::factory()->create(['access_token_id' => $otherToken->id]);
    Card::factory()->create(['deck_id' => $otherDeck->id, 'aced_count' => 0, 'missed_count' => 3]);

    expect(app(CountCardsToReinforce::class)->handle($token->id))->toBe(1);
});
