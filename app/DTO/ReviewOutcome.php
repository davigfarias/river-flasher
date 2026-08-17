<?php

declare(strict_types=1);

namespace App\DTO;

use Carbon\CarbonImmutable;

final readonly class ReviewOutcome
{
    /**
     * Below this many minutes, the label is expressed in minutes.
     */
    private const int MINUTE_CUTOFF = 60;

    /**
     * Below this many minutes (1 day), the label is expressed in hours.
     */
    private const int HOUR_CUTOFF = 1_440;

    /**
     * Below this many minutes (10 days), a day-scale label keeps one
     * decimal of precision (e.g. "3.5d"); at or above it, whole days.
     */
    private const int DECIMAL_DAY_CUTOFF = 14_400;

    /**
     * Below this many minutes (30 days), the label is expressed in days;
     * at or above it, months.
     */
    private const int MONTH_CUTOFF = 43_200;

    public function __construct(
        public float $easeFactor,
        public int $intervalMinutes,
        public int $repetitions,
        public CarbonImmutable $dueAt,
    ) {}

    /**
     * A short, human-readable rendering of the interval, used for the
     * study session's rating button hints (e.g. "1m", "6m", "3.5d").
     */
    public function intervalLabel(): string
    {
        $minutes = $this->intervalMinutes;

        if ($minutes < self::MINUTE_CUTOFF) {
            return "{$minutes}m";
        }

        if ($minutes < self::HOUR_CUTOFF) {
            return intdiv($minutes, 60).'h';
        }

        if ($minutes < self::MONTH_CUTOFF) {
            $days = $minutes / 1_440;

            $formatted = $minutes < self::DECIMAL_DAY_CUTOFF
                ? rtrim(rtrim(number_format($days, 1), '0'), '.')
                : (string) round($days);

            return "{$formatted}d";
        }

        $months = max(1, (int) round($minutes / self::MONTH_CUTOFF));

        return "{$months}mo";
    }
}
