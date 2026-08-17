<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class DeckData
{
    public function __construct(
        public string $name,
        public string $icon,
        public string $color,
    ) {}
}
