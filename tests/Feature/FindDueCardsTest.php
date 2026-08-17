<?php

use App\Actions\FindDueCards;
use App\Models\AccessToken;
use App\Models\Card;
use App\Models\Deck;
use Carbon\CarbonImmutable;

beforeEach(function () {
    $this->token = AccessToken::factory()->create();
    $this->deck = Deck::factory()->create(['access_token_id' => $this->token->id]);
    $this->now = CarbonImmutable::now();
});

test('it includes due and brand-new cards, and excludes cards due in the future', function () {
    $due = Card::factory()->dueNow()->create(['deck_id' => $this->deck->id]);
    $future = Card::factory()->create(['deck_id' => $this->deck->id, 'due_at' => $this->now->addDay()]);

    $result = app(FindDueCards::class)->handle($this->token->id, null, $this->now);

    expect($result->pluck('id'))->toContain($due->id)
        ->and($result->pluck('id'))->not->toContain($future->id);
});

test('it scopes to a single deck when one is given', function () {
    $otherDeck = Deck::factory()->create(['access_token_id' => $this->token->id]);

    $inDeck = Card::factory()->dueNow()->create(['deck_id' => $this->deck->id]);
    Card::factory()->dueNow()->create(['deck_id' => $otherDeck->id]);

    $result = app(FindDueCards::class)->handle($this->token->id, $this->deck, $this->now);

    expect($result->pluck('id')->all())->toBe([$inDeck->id]);
});

test('it never returns cards belonging to another token', function () {
    $otherToken = AccessToken::factory()->create();
    $otherDeck = Deck::factory()->create(['access_token_id' => $otherToken->id]);
    Card::factory()->dueNow()->create(['deck_id' => $otherDeck->id]);

    $result = app(FindDueCards::class)->handle($this->token->id, null, $this->now);

    expect($result)->toBeEmpty();
});

test('it orders by due_at ascending and respects the limit', function () {
    $later = Card::factory()->create(['deck_id' => $this->deck->id, 'due_at' => $this->now->subMinutes(5)]);
    $earlier = Card::factory()->create(['deck_id' => $this->deck->id, 'due_at' => $this->now->subMinutes(50)]);

    $result = app(FindDueCards::class)->handle($this->token->id, null, $this->now, limit: 1);

    expect($result)->toHaveCount(1)
        ->and($result->first()->id)->toBe($earlier->id)
        ->and($result->pluck('id'))->not->toContain($later->id);
});
