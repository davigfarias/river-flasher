<?php

declare(strict_types=1);

namespace App\DTO;

use App\Models\Card;

final readonly class SrsState
{
    public function __construct(
        public float $easeFactor,
        public int $intervalMinutes,
        public int $repetitions,
    ) {}

    public static function fromCard(Card $card): self
    {
        return new self(
            easeFactor: $card->ease_factor,
            intervalMinutes: $card->interval_minutes,
            repetitions: $card->repetitions,
        );
    }
}
