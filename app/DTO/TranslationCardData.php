<?php

declare(strict_types=1);

namespace App\DTO;

/**
 * Everything the tradução study screen needs for one card: the target-language
 * sentence to translate, the correct ordered translation tokens, and the
 * shuffled word bank (correct tokens + deck distractors) the user taps from.
 */
final readonly class TranslationCardData
{
    /**
     * @param  array<int, string>  $correctTokens
     * @param  array<int, string>  $wordBank
     */
    public function __construct(
        public string $targetText,
        public array $correctTokens,
        public array $wordBank,
    ) {}
}
