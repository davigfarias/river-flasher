<?php

declare(strict_types=1);

namespace App\DTO;

use App\Enums\Language;
use Livewire\Wireable;

/**
 * Implements Wireable so a Livewire component can hold this as a public
 * property directly — Livewire has no built-in synth for plain DTOs.
 */
final readonly class ParsedCsv implements Wireable
{
    /**
     * @param  array<int, array{word: string, definition: string, transliteration: ?string, example: ?string, translation: ?string, pos: ?string, category: ?string, isDifficult: bool}>  $rows
     * @param  array<int, string>  $rowErrors  line number => reason
     * @param  array<int, string>  $missingColumns
     */
    public function __construct(
        public array $rows,
        public array $rowErrors,
        public array $missingColumns,
        public bool $languagesMixed,
        public ?Language $detectedLanguage,
    ) {}

    /**
     * @return array{rows: array<int, array{word: string, definition: string, transliteration: ?string, example: ?string, translation: ?string, pos: ?string, category: ?string, isDifficult: bool}>, rowErrors: array<int, string>, missingColumns: array<int, string>, languagesMixed: bool, detectedLanguage: ?string}
     */
    public function toLivewire(): array
    {
        return [
            'rows' => $this->rows,
            'rowErrors' => $this->rowErrors,
            'missingColumns' => $this->missingColumns,
            'languagesMixed' => $this->languagesMixed,
            'detectedLanguage' => $this->detectedLanguage?->value,
        ];
    }

    /**
     * @param  array{rows: array<int, array{word: string, definition: string, transliteration: ?string, example: ?string, translation: ?string, pos: ?string, category: ?string, isDifficult: bool}>, rowErrors: array<int, string>, missingColumns: array<int, string>, languagesMixed: bool, detectedLanguage: ?string}  $value
     */
    public static function fromLivewire($value): static
    {
        return new self(
            rows: $value['rows'],
            rowErrors: $value['rowErrors'],
            missingColumns: $value['missingColumns'],
            languagesMixed: $value['languagesMixed'],
            detectedLanguage: $value['detectedLanguage'] !== null ? Language::from($value['detectedLanguage']) : null,
        );
    }
}
