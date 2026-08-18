<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class RecallCounters
{
    public function __construct(
        public int $acedCount,
        public int $missedCount,
    ) {}
}
