<?php

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
