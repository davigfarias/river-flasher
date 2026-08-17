<?php

use App\Actions\Orchestrators\BuildDashboardOrchestrator;
use App\Enums\ActivityRange;
use App\Models\AccessToken;
use App\Models\Card;
use App\Models\Deck;

test('it assembles every dashboard section into one shape', function () {
    $token = AccessToken::factory()->create();
    $deck = Deck::factory()->create(['access_token_id' => $token->id, 'name' => 'Test Deck']);
    Card::factory()->count(3)->dueNow()->create(['deck_id' => $deck->id]);
    Card::factory()->mature()->create(['deck_id' => $deck->id]);

    $data = app(BuildDashboardOrchestrator::class)->handle($token->id, ActivityRange::Week);

    expect($data->dueToday)->toBe(3)
        ->and($data->masteredTotal)->toBe(1)
        ->and($data->todayProgressPercent)->toBeInt()
        ->and($data->streak->days)->toBeInt()
        ->and($data->streak->last7)->toHaveCount(7)
        ->and($data->activity)->toHaveCount(7)
        ->and($data->recentDecks)->toHaveCount(1)
        ->and($data->recentDecks[0]['name'])->toBe('Test Deck')
        ->and($data->recentDecks[0]['meta'])->toBe('3 due')
        ->and($data->recentDecks[0]['urgent'])->toBeFalse();
});

test('it never leaks another token\'s decks or cards into the summary', function () {
    $token = AccessToken::factory()->create();
    Deck::factory()->create(['access_token_id' => $token->id]);

    $otherToken = AccessToken::factory()->create();
    $otherDeck = Deck::factory()->create(['access_token_id' => $otherToken->id]);
    Card::factory()->dueNow()->create(['deck_id' => $otherDeck->id]);

    $data = app(BuildDashboardOrchestrator::class)->handle($token->id, ActivityRange::Week);

    expect($data->dueToday)->toBe(0)
        ->and($data->recentDecks)->toHaveCount(1);
});
