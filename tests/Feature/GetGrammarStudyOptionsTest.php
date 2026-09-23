<?php

use App\Actions\GetGrammarStudyOptions;
use App\Models\AccessToken;
use App\Models\Card;
use App\Models\Deck;

beforeEach(function () {
    $this->token = AccessToken::factory()->create();
    $this->deck = Deck::factory()->create(['access_token_id' => $this->token->id]);
});

test('it counts declinable cards ready for tradução, grouped by paradigm', function () {
    Card::factory()->declinable(paradigmSlug: 'noun-2-masc')->withSentence()->create(['deck_id' => $this->deck->id]);
    Card::factory()->declinable(paradigmSlug: 'noun-2-masc')->withSentence()->create(['deck_id' => $this->deck->id]);
    Card::factory()->declinable(paradigmSlug: 'noun-1-fem-eta')->withSentence()->create(['deck_id' => $this->deck->id]);

    $options = app(GetGrammarStudyOptions::class)->handle($this->token->id);

    expect($options->sortKeys()->all())->toBe(['noun-1-fem-eta' => 1, 'noun-2-masc' => 2]);
});

test('a card without a paradigm, or without a ready sentence, or inactive, does not count', function () {
    Card::factory()->create(['deck_id' => $this->deck->id]); // no paradigm_slug
    Card::factory()->declinable()->withoutSentence()->create(['deck_id' => $this->deck->id]);
    Card::factory()->declinable()->withSentence()->inactive()->create(['deck_id' => $this->deck->id]);

    $options = app(GetGrammarStudyOptions::class)->handle($this->token->id);

    expect($options->all())->toBe([]);
});

test('it scopes to the given decks and to the token', function () {
    $otherDeck = Deck::factory()->create(['access_token_id' => $this->token->id]);
    $otherToken = AccessToken::factory()->create();
    $otherTokenDeck = Deck::factory()->create(['access_token_id' => $otherToken->id]);

    Card::factory()->declinable()->withSentence()->create(['deck_id' => $this->deck->id]);
    Card::factory()->declinable()->withSentence()->create(['deck_id' => $otherDeck->id]);
    Card::factory()->declinable()->withSentence()->create(['deck_id' => $otherTokenDeck->id]);

    $options = app(GetGrammarStudyOptions::class)->handle($this->token->id, [$this->deck->id]);

    expect($options->all())->toBe(['noun-2-masc' => 1]);
});
