<?php

declare(strict_types=1);

namespace App\Support\Grammar;

use App\Enums\GrammaticalCase;
use App\Enums\GrammaticalNumber;
use App\Enums\GrammaticalPerson;
use InvalidArgumentException;

/**
 * An immutable inflection table, built from one entry of
 * config/grammar/{language}.php. Holds nothing but the endings — the
 * concatenation itself lives in App\Actions\GenerateInflectedForm /
 * GenerateInflectedVerbForm.
 */
final readonly class Paradigm
{
    /**
     * @param  array<string, array<string, string|null>>  $endings  noun: case key => ['sg' => ?string, 'pl' => ?string]; verb: person key ('1'|'2'|'3') => ['sg' => ?string, 'pl' => ?string]
     */
    public function __construct(
        public string $slug,
        public string $label,
        public ?string $pos,
        public ?string $gender,
        public ?string $voice,
        public array $endings,
    ) {}

    /**
     * @param  array{label?: string, pos?: string|null, gender?: string|null, voice?: string|null, endings?: array<string, array<string, string|null>>}  $config
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
            voice: $config['voice'] ?? null,
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

    /**
     * The ending for a verb's person/number cell.
     */
    public function endingForPerson(GrammaticalPerson $person, GrammaticalNumber $number): ?string
    {
        return $this->endings[$person->value][$number->value] ?? null;
    }
}
