<?php

declare(strict_types=1);

namespace App\DTO;

use App\Enums\StudyMode;

final readonly class StudySessionData
{
    /**
     * @param  array<int, int>  $cardIds
     */
    public function __construct(
        public string $deckName,
        public array $cardIds,
        public StudyMode $mode = StudyMode::Meaning,
    ) {}
}
