<?php

use App\Actions\Orchestrators\AnswerCardOrchestrator;
use App\Enums\ReviewResult;
use App\Models\AccessToken;
use App\Models\Card;
use App\Models\Deck;
use App\Models\Review;

beforeEach(function () {
    $this->token = AccessToken::factory()->create();
    $this->deck = Deck::factory()->create(['access_token_id' => $this->token->id]);
});

test('remembering a card persists the new counters and records the review', function () {
    $card = Card::factory()->create(['deck_id' => $this->deck->id, 'aced_count' => 1, 'missed_count' => 2]);

    $counters = app(AnswerCardOrchestrator::class)->handle($card, ReviewResult::Remembered);

    expect($counters->acedCount)->toBe(2)
        ->and($counters->missedCount)->toBe(2);

    $card->refresh();

    expect($card->aced_count)->toBe(2)
        ->and($card->missed_count)->toBe(2)
        ->and($card->last_reviewed_at)->not->toBeNull();

    $review = Review::where('card_id', $card->id)->sole();

    expect($review->result)->toBe(ReviewResult::Remembered)
        ->and($review->access_token_id)->toBe($this->token->id);
});

test('forgetting a card increments missed_count and records the review', function () {
    $card = Card::factory()->create(['deck_id' => $this->deck->id, 'aced_count' => 1, 'missed_count' => 0]);

    app(AnswerCardOrchestrator::class)->handle($card, ReviewResult::Forgot);

    $card->refresh();

    expect($card->aced_count)->toBe(1)
        ->and($card->missed_count)->toBe(1);

    expect(Review::where('card_id', $card->id)->sole()->result)->toBe(ReviewResult::Forgot);
});
