<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTO\RecallCounters;
use App\Enums\ReviewResult;

/**
 * Pure recall-counter math: given a card's current aced/missed counts and
 * whether it was remembered or forgotten, computes its next counts.
 *
 * No database access, no clock reads — the single source of truth for how
 * "lembrei"/"não lembrei" changes a card's standing.
 */
final readonly class CalculateRecallCounters
{
    public function handle(int $acedCount, int $missedCount, ReviewResult $result): RecallCounters
    {
        return new RecallCounters(
            acedCount: $result->remembered() ? $acedCount + 1 : $acedCount,
            missedCount: $result->remembered() ? $missedCount : $missedCount + 1,
        );
    }
}
