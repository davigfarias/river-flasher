<?php

declare(strict_types=1);

namespace App\Actions;

/**
 * Pure sequence match for the tradução mode: does the ordered list of tiles
 * the user tapped spell out the card's correct translation? Comparison is
 * case-insensitive and ignores surrounding punctuation, but respects order,
 * accents and word count (a missing or extra word fails).
 */
final readonly class CompareTranslationTokens
{
    /**
     * @param  array<int, string>  $submitted
     * @param  array<int, string>  $correct
     */
    public function handle(array $submitted, array $correct): bool
    {
        return $this->normalize($submitted) === $this->normalize($correct)
            && $this->normalize($correct) !== [];
    }

    /**
     * @param  array<int, string>  $tokens
     * @return array<int, string>
     */
    private function normalize(array $tokens): array
    {
        $normalized = [];

        foreach ($tokens as $token) {
            $clean = preg_replace('/^[\p{P}\p{S}]+|[\p{P}\p{S}]+$/u', '', trim($token));

            if ($clean !== null && $clean !== '') {
                $normalized[] = mb_strtolower($clean);
            }
        }

        return $normalized;
    }
}
