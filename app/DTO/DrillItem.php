<?php

declare(strict_types=1);

namespace App\DTO;

use Livewire\Wireable;

/**
 * One Tap Complete exercise: a sentence with one token blanked out and a
 * word bank of the correct form plus distractors (the same lexeme in
 * other cases/numbers). Wireable so the drill component can hold it
 * across requests, like ParsedCsv.
 */
final readonly class DrillItem implements Wireable
{
    /**
     * @param  array<int, string>  $before  surfaces before the blank
     * @param  array<int, string>  $after  surfaces after the blank
     * @param  array<int, string>  $options  shuffled word bank
     */
    public function __construct(
        public int $sentenceId,
        public int $tokenId,
        public array $before,
        public array $after,
        public array $options,
        public string $correctSurface,
        public string $analysis,
        public string $lemma,
        public string $translation,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toLivewire(): array
    {
        return [
            'sentenceId' => $this->sentenceId,
            'tokenId' => $this->tokenId,
            'before' => $this->before,
            'after' => $this->after,
            'options' => $this->options,
            'correctSurface' => $this->correctSurface,
            'analysis' => $this->analysis,
            'lemma' => $this->lemma,
            'translation' => $this->translation,
        ];
    }

    /**
     * @param  array<string, mixed>  $value
     */
    public static function fromLivewire($value): self
    {
        return new self(
            sentenceId: $value['sentenceId'],
            tokenId: $value['tokenId'],
            before: $value['before'],
            after: $value['after'],
            options: $value['options'],
            correctSurface: $value['correctSurface'],
            analysis: $value['analysis'],
            lemma: $value['lemma'],
            translation: $value['translation'],
        );
    }
}
