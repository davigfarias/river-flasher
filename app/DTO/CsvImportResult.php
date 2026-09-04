<?php

declare(strict_types=1);

namespace App\DTO;

use App\Models\Deck;

final readonly class CsvImportResult
{
    public function __construct(
        public Deck $deck,
        public int $importedCount,
    ) {}
}
