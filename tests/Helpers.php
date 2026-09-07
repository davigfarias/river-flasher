<?php

use App\Enums\Gender;
use App\Enums\GrammaticalCase;
use App\Enums\GrammaticalNumber;
use App\Enums\Language;
use App\Models\Card;
use App\Models\Deck;
use App\Models\Sentence;
use App\Models\SentenceToken;

/**
 * Logs into the app through the real OTP flow, for browser tests. Each
 * digit is pressed into its own box by aria-label — the OTP widget
 * auto-advances focus via JS, so `type()`/`fill()` on the group doesn't
 * work, but a real per-box keypress does.
 */
function loginWithCode($page, string $code)
{
    foreach (str_split($code) as $position => $digit) {
        $page->keys('input[aria-label="Character '.($position + 1).' of '.mb_strlen($code).'"]', $digit);
    }

    return $page;
}

/**
 * A well-formed GreekSentenceWriter response: a 3-word dative sentence
 * whose noun (ἄνθρωπος, stem ανθρωπ, 2nd-decl masc) is correctly inflected.
 * Used to fake the agent in the sentence-generation tests.
 *
 * @return array<string, mixed>
 */
function validDativeSentence(): array
{
    return [
        'text' => 'βλεπω τῳ ανθρωπῳ',
        'translation_pt' => 'vejo o homem',
        'tokens' => [
            ['surface' => 'βλεπω', 'lemma' => 'βλεπω', 'case' => null, 'number' => null],
            ['surface' => 'τῳ', 'lemma' => 'ὁ', 'case' => 'dat', 'number' => 'sg'],
            ['surface' => 'ανθρωπῳ', 'lemma' => 'ἄνθρωπος', 'case' => 'dat', 'number' => 'sg'],
        ],
    ];
}

/**
 * Same shape, but the noun is tagged dative while carrying the accusative
 * form — the deterministic validator must reject it.
 *
 * @return array<string, mixed>
 */
function morphologicallyWrongSentence(): array
{
    return [
        'text' => 'βλεπω τον ανθρωπον',
        'translation_pt' => 'vejo o homem',
        'tokens' => [
            ['surface' => 'βλεπω', 'lemma' => 'βλεπω', 'case' => null, 'number' => null],
            ['surface' => 'τον', 'lemma' => 'ὁ', 'case' => 'acc', 'number' => 'sg'],
            ['surface' => 'ανθρωπον', 'lemma' => 'ἄνθρωπος', 'case' => 'dat', 'number' => 'sg'],
        ],
    ];
}

/**
 * Seeds an approved, drillable sentence on $deck: "βλεπω τῳ <surface>" with
 * the third token linked to a declinable ἄνθρωπος card (2nd-decl masc,
 * stem ανθρωπ) and marked as the drill target (dative singular).
 *
 * @return array{0: Sentence, 1: SentenceToken, 2: Card}
 */
function seedDrillSentence(Deck $deck, string $surface = 'ανθρωπῳ'): array
{
    $card = Card::factory()
        ->declinable('ανθρωπ', 'noun-2-masc', Gender::Masculine)
        ->create(['deck_id' => $deck->id, 'word' => 'ἄνθρωπος', 'language' => Language::Greek]);

    $sentence = Sentence::factory()->approved()->create([
        'access_token_id' => $deck->access_token_id,
        'deck_id' => $deck->id,
        'text' => "βλεπω τῳ {$surface}",
        'translation_pt' => 'vejo o homem',
        'grammar_focus' => 'dat',
    ]);

    SentenceToken::factory()->for($sentence)->create(['position' => 0, 'surface' => 'βλεπω']);
    SentenceToken::factory()->for($sentence)->create(['position' => 1, 'surface' => 'τῳ']);
    $target = SentenceToken::factory()->for($sentence)
        ->target(GrammaticalCase::Dative, GrammaticalNumber::Singular)
        ->create(['position' => 2, 'surface' => $surface, 'card_id' => $card->id]);

    return [$sentence, $target, $card];
}
