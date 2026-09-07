<?php

declare(strict_types=1);

namespace App\DTO;

use App\Enums\Gender;

/**
 * The morphology annotation for a single card, as entered on the
 * deck-morphology screen. Empty values clear the column.
 */
final readonly class CardMorphology
{
    public function __construct(
        public ?string $stem,
        public ?string $paradigmSlug,
        public ?Gender $gender,
        public ?string $nomSgOverride,
    ) {}
}
