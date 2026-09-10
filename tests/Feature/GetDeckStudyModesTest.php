<?php

use App\Actions\GetDeckStudyModes;
use App\Enums\StudyMode;
use App\Models\AccessToken;
use App\Models\Card;
use App\Models\Deck;

beforeEach(function () {
    $this->token = AccessToken::factory()->create();
    $this->deck = Deck::factory()->create(['access_token_id' => $this->token->id]);
});

test('significado is always available; leitura and tradução need the right data', function () {
    Card::factory()->create([
        'deck_id' => $this->deck->id,
        'transliteration' => null,
        'example' => null,
        'translation' => null,
    ]);

    $modes = app(GetDeckStudyModes::class)->handle($this->token->id, [$this->deck->id]);

    expect($modes[StudyMode::Meaning->value])->toBeTrue()
        ->and($modes[StudyMode::Reading->value])->toBeFalse()
        ->and($modes[StudyMode::Translation->value])->toBeFalse();
});

test('leitura unlocks once a card carries a transliteration', function () {
    Card::factory()->create(['deck_id' => $this->deck->id, 'transliteration' => 'agápē']);

    $modes = app(GetDeckStudyModes::class)->handle($this->token->id, [$this->deck->id]);

    expect($modes[StudyMode::Reading->value])->toBeTrue();
});

test('tradução unlocks once at least five cards have an example and translation', function () {
    Card::factory()->count(4)->withSentence()->create(['deck_id' => $this->deck->id]);

    expect(app(GetDeckStudyModes::class)->handle($this->token->id, [$this->deck->id])[StudyMode::Translation->value])->toBeFalse();

    Card::factory()->withSentence()->create(['deck_id' => $this->deck->id]);

    expect(app(GetDeckStudyModes::class)->handle($this->token->id, [$this->deck->id])[StudyMode::Translation->value])->toBeTrue();
});

test('inactive cards do not count toward mode availability', function () {
    Card::factory()->count(6)->withSentence()->inactive()->create(['deck_id' => $this->deck->id]);

    expect(app(GetDeckStudyModes::class)->handle($this->token->id, [$this->deck->id])[StudyMode::Translation->value])->toBeFalse();
});
