<?php

use App\Actions\Orchestrators\GenerateSentencesOrchestrator;
use App\Ai\Agents\GreekSentenceWriter;
use App\Enums\Gender;
use App\Enums\GrammaticalCase;
use App\Enums\Language;
use App\Enums\SentenceSource;
use App\Enums\SentenceStatus;
use App\Models\AccessToken;
use App\Models\Card;
use App\Models\Deck;
use App\Models\Sentence;

beforeEach(function () {
    $this->token = AccessToken::factory()->create();
    $this->deck = Deck::factory()->create(['access_token_id' => $this->token->id]);

    $this->card = Card::factory()
        ->declinable('ανθρωπ', 'noun-2-masc', Gender::Masculine)
        ->create(['deck_id' => $this->deck->id, 'word' => 'ἄνθρωπος', 'language' => Language::Greek]);
});

test('a valid sentence is stored pending and a morphologically wrong one is rejected', function () {
    GreekSentenceWriter::fake([
        ['sentences' => [validDativeSentence(), morphologicallyWrongSentence()]],
    ]);

    $summary = app(GenerateSentencesOrchestrator::class)->handle(
        $this->token->id,
        $this->deck,
        GrammaticalCase::Dative,
        calls: 1,
    );

    expect($summary->generated)->toBe(2)
        ->and($summary->accepted)->toBe(1)
        ->and($summary->rejected)->toBe(1)
        ->and($summary->rejectionReasons[0])->toContain('morfologia errada');

    $sentence = Sentence::sole();

    expect($sentence->status)->toBe(SentenceStatus::Pending)
        ->and($sentence->source)->toBe(SentenceSource::Ai)
        ->and($sentence->grammar_focus)->toBe('dat')
        ->and($sentence->tokens)->toHaveCount(3)
        ->and($sentence->tokens->firstWhere('surface', 'ανθρωπῳ')->is_target)->toBeTrue()
        ->and($sentence->tokens->firstWhere('surface', 'ανθρωπῳ')->card_id)->toBe($this->card->id)
        ->and($sentence->tokens->firstWhere('surface', 'βλεπω')->is_target)->toBeFalse();
});

test('generation is blocked when the deck has no declinable cards', function () {
    $bareDeck = Deck::factory()->create(['access_token_id' => $this->token->id]);
    Card::factory()->create(['deck_id' => $bareDeck->id, 'language' => Language::Greek]);

    GreekSentenceWriter::fake([['sentences' => [validDativeSentence()]]]);

    $summary = app(GenerateSentencesOrchestrator::class)->handle(
        $this->token->id,
        $bareDeck,
        GrammaticalCase::Dative,
        calls: 1,
    );

    expect($summary->blockedReason)->not->toBeNull()
        ->and($summary->accepted)->toBe(0)
        ->and(Sentence::count())->toBe(0);

    GreekSentenceWriter::assertNeverPrompted();
});

test('duplicate sentences across calls are only stored once', function () {
    GreekSentenceWriter::fake([
        ['sentences' => [validDativeSentence()]],
        ['sentences' => [validDativeSentence()]],
    ]);

    $summary = app(GenerateSentencesOrchestrator::class)->handle(
        $this->token->id,
        $this->deck,
        GrammaticalCase::Dative,
        calls: 2,
    );

    expect($summary->generated)->toBe(1)
        ->and($summary->accepted)->toBe(1)
        ->and(Sentence::count())->toBe(1);
});
