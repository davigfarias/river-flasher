<?php

use App\Actions\Orchestrators\RateCardOrchestrator;
use App\Enums\CardRating;
use App\Models\AccessToken;
use App\Models\Card;
use App\Models\Deck;
use App\Models\Review;

beforeEach(function () {
    $this->token = AccessToken::factory()->create();
    $this->deck = Deck::factory()->create(['access_token_id' => $this->token->id]);
});

test('it persists the new SRS state on the card and logs a review row', function () {
    $card = Card::factory()->create([
        'deck_id' => $this->deck->id,
        'ease_factor' => 2.5,
        'interval_minutes' => 0,
        'repetitions' => 0,
    ]);

    $outcome = app(RateCardOrchestrator::class)->handle($card, CardRating::Good);

    $card->refresh();

    expect($card->interval_minutes)->toBe(10)
        ->and($card->ease_factor)->toBe(2.5)
        ->and($card->repetitions)->toBe(0)
        ->and($card->last_reviewed_at)->not->toBeNull()
        ->and($card->due_at->timestamp)->toBe($outcome->dueAt->timestamp);

    $review = Review::sole();

    expect($review->card_id)->toBe($card->id)
        ->and($review->access_token_id)->toBe($this->token->id)
        ->and($review->rating)->toBe(CardRating::Good)
        ->and($review->interval_minutes_after)->toBe(10);
});

test('it is atomic: card state and review log always move together', function () {
    $card = Card::factory()->create(['deck_id' => $this->deck->id]);

    app(RateCardOrchestrator::class)->handle($card, CardRating::Easy);

    expect(Review::count())->toBe(1)
        ->and($card->refresh()->interval_minutes)->toBe(Review::sole()->interval_minutes_after);
});
