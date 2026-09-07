<?php

declare(strict_types=1);

namespace App\Support\Grammar;

use App\Enums\GrammaticalCase;
use App\Enums\GrammaticalNumber;
use InvalidArgumentException;

/**
 * An immutable inflection table, built from one entry of
 * config/grammar/{language}.php. Holds nothing but the endings — the
 * concatenation itself lives in App\Actions\GenerateInflectedForm.
 */
final readonly class Paradigm
{
    /**
     * @param  array<string, array<string, string|null>>  $endings  case key => ['sg' => ?string, 'pl' => ?string]
     */
    public function __construct(
        public string $slug,
        public string $label,
        public ?string $pos,
        public ?string $gender,
        public array $endings,
    ) {}

    /**
     * @param  array{label?: string, pos?: string|null, gender?: string|null, endings?: array<string, array<string, string|null>>}  $config
     */
    public static function fromConfig(string $slug, array $config): self
    {
        if (! isset($config['endings']) || $config['endings'] === []) {
            throw new InvalidArgumentException("Paradigm [{$slug}] has no endings defined.");
        }

        return new self(
            slug: $slug,
            label: $config['label'] ?? $slug,
            pos: $config['pos'] ?? null,
            gender: $config['gender'] ?? null,
            endings: $config['endings'],
        );
    }

    /**
     * The ending for a case/number cell, or null when the paradigm can't
     * predict that form from the stem (e.g. 3rd-declension nominative
     * singular) — the caller then falls back to the card's
     * `nom_sg_override`.
     */
    public function endingFor(GrammaticalCase $case, GrammaticalNumber $number): ?string
    {
        return $this->endings[$case->value][$number->value] ?? null;
    }
}
