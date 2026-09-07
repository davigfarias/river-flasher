<?php

declare(strict_types=1);

namespace App\DTO;

use App\Enums\GrammaticalCase;
use App\Enums\GrammaticalNumber;

final readonly class SentenceTokenData
{
    public function __construct(
        public int $position,
        public string $surface,
        public ?string $lemma,
        public ?GrammaticalCase $case,
        public ?GrammaticalNumber $number,
    ) {}
}
