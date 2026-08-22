<?php

declare(strict_types=1);

namespace App\DTO;

use App\Enums\Language;

final readonly class CardData
{
    public function __construct(
        public Language $language,
        public string $word,
        public ?string $transliteration,
        public string $definition,
        public ?string $example,
        public ?string $translation,
        public ?string $pos,
        public bool $isDifficult,
        public ?string $imagePath,
    ) {}
}
