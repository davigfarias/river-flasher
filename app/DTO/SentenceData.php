<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class SentenceData
{
    /**
     * @param  array<int, SentenceTokenData>  $tokens
     */
    public function __construct(
        public string $text,
        public string $translationPt,
        public ?string $grammarFocus,
        public array $tokens,
    ) {}
}
