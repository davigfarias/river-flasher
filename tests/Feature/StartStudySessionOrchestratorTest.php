<?php

use App\Actions\Orchestrators\StartStudySessionOrchestrator;
use App\Models\AccessToken;
use App\Models\Card;
use App\Models\Deck;
use Illuminate\Database\Eloquent\Collection;

beforeEach(function () {
    $this->token = AccessToken::factory()->create();
});

test('an empty deck collection studies everything, named "Todos os baralhos"', function () {
    $deck = Deck::factory()->create(['access_token_id' => $this->token->id]);
    $card = Card::factory()->create(['deck_id' => $deck->id]);

    $session = app(StartStudySessionOrchestrator::class)->handle($this->token->id, new Collection);

    expect($session->deckName)->toBe('Todos os baralhos')
        ->and($session->cardIds)->toBe([$card->id]);
});

test('a single deck is named after that deck', function () {
    $deck = Deck::factory()->create(['access_token_id' => $this->token->id, 'name' => 'Koine Greek']);
    Card::factory()->create(['deck_id' => $deck->id]);

    $session = app(StartStudySessionOrchestrator::class)->handle($this->token->id, new Collection([$deck]));

    expect($session->deckName)->toBe('Koine Greek');
});

test('several decks build one combined session, named by count', function () {
    $deckA = Deck::factory()->create(['access_token_id' => $this->token->id]);
    $deckB = Deck::factory()->create(['access_token_id' => $this->token->id]);
    $excluded = Deck::factory()->create(['access_token_id' => $this->token->id]);

    $cardA = Card::factory()->create(['deck_id' => $deckA->id]);
    $cardB = Card::factory()->create(['deck_id' => $deckB->id]);
    Card::factory()->create(['deck_id' => $excluded->id]);

    $session = app(StartStudySessionOrchestrator::class)->handle($this->token->id, new Collection([$deckA, $deckB]));

    expect($session->deckName)->toBe('2 baralhos selecionados')
        ->and($session->cardIds)->toEqualCanonicalizing([$cardA->id, $cardB->id]);
});
