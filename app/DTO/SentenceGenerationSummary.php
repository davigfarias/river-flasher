<?php

declare(strict_types=1);

namespace App\DTO;

/**
 * The outcome of one "Gerar frases" run, rendered back to the review
 * screen as a toast + panel.
 */
final readonly class SentenceGenerationSummary
{
    /**
     * @param  array<int, string>  $rejectionReasons  one line per discarded sentence
     */
    public function __construct(
        public int $generated,
        public int $accepted,
        public int $rejected,
        public array $rejectionReasons,
        public ?string $blockedReason = null,
    ) {}

    public static function blocked(string $reason): self
    {
        return new self(0, 0, 0, [], $reason);
    }
}
