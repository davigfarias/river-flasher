<?php

use App\Actions\Orchestrators\BuildDrillItemOrchestrator;
use App\Models\Deck;
use App\Models\Sentence;
use App\Support\Grammar\GreekFormComparator;

test('it blanks the target token and keeps the rest of the sentence', function () {
    $deck = Deck::factory()->create();
    [$sentence, $target] = seedDrillSentence($deck);

    $item = app(BuildDrillItemOrchestrator::class)->handle($deck->id);

    expect($item->sentenceId)->toBe($sentence->id)
        ->and($item->tokenId)->toBe($target->id)
        ->and($item->before)->toBe(['βλεπω', 'τῳ'])
        ->and($item->after)->toBe([])
        ->and($item->correctSurface)->toBe('ανθρωπῳ')
        ->and($item->analysis)->toBe('dativo singular');
});

test('the word bank holds the correct form plus distinct same-lexeme distractors', function () {
    $deck = Deck::factory()->create();
    seedDrillSentence($deck);

    $item = app(BuildDrillItemOrchestrator::class)->handle($deck->id);
    $comparator = new GreekFormComparator;

    expect($item->options)->toHaveCount(4)
        ->and(collect($item->options)->contains(fn ($o) => $comparator->matches($o, 'ανθρωπῳ')))->toBeTrue();

    // Every option is a real inflected form of ανθρωπ- (2nd-decl masc), and
    // no two options collide once diacritics are stripped.
    $normalised = array_map(fn ($o) => $comparator->normalize($o), $item->options);
    expect($normalised)->toHaveCount(count(array_unique($normalised)));
});

test('it returns null when the deck has no approved drillable sentence', function () {
    $deck = Deck::factory()->create();
    Sentence::factory()->create(['deck_id' => $deck->id, 'access_token_id' => $deck->access_token_id]); // pending, not approved

    expect(app(BuildDrillItemOrchestrator::class)->handle($deck->id))->toBeNull();
});
