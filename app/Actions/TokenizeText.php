<?php

declare(strict_types=1);

namespace App\Actions;

/**
 * Splits a sentence into display tokens for the tradução word bank: one token
 * per whitespace-separated word, with surrounding punctuation stripped but
 * casing and word-internal marks (apostrophes, hyphens) left intact. Empty
 * results are dropped.
 */
final readonly class TokenizeText
{
    /**
     * @return array<int, string>
     */
    public function handle(string $text): array
    {
        $pieces = preg_split('/\s+/u', trim($text)) ?: [];

        $tokens = [];

        foreach ($pieces as $piece) {
            $clean = preg_replace('/^[\p{P}\p{S}]+|[\p{P}\p{S}]+$/u', '', $piece);

            if ($clean !== null && $clean !== '') {
                $tokens[] = $clean;
            }
        }

        return $tokens;
    }
}
