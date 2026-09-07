<?php

declare(strict_types=1);

namespace App\Support\Grammar;

use Normalizer;

/**
 * Compares two Greek word forms for the drills. The generator concatenates
 * stem + ending and can't place the accent, so the comparison is
 * deliberately accent-blind: it strips every diacritic (accents, breathings,
 * iota subscript) and folds case and final sigma before comparing.
 */
final readonly class GreekFormComparator
{
    public function matches(string $expected, string $given): bool
    {
        return $this->normalize($expected) === $this->normalize($given)
            && $this->normalize($given) !== '';
    }

    /**
     * NFD-decompose, drop combining marks (this also removes the iota
     * subscript, which decomposes to U+0345), lowercase, fold final sigma,
     * and trim surrounding punctuation/whitespace.
     */
    public function normalize(string $value): string
    {
        $decomposed = Normalizer::normalize($value, Normalizer::FORM_D);

        if ($decomposed === false) {
            $decomposed = $value;
        }

        $stripped = preg_replace('/\p{Mn}+/u', '', $decomposed) ?? $decomposed;

        $lower = mb_strtolower($stripped);

        $sigmaFolded = str_replace('ς', 'σ', $lower);

        return trim($sigmaFolded, " \t\n\r\0\x0B.,·;:!?\"'()[]{}«»");
    }
}
