<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use App\DTO\SentenceData;
use App\DTO\SentenceTokenData;
use App\Enums\GrammaticalCase;
use Illuminate\Validation\Rule;
use Livewire\Form;

class SentenceForm extends Form
{
    public string $text = '';

    public string $translationPt = '';

    public string $grammarFocus = '';

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'text' => ['required', 'string', 'max:500'],
            'translationPt' => ['required', 'string', 'max:500'],
            'grammarFocus' => ['nullable', Rule::in(array_column(GrammaticalCase::cases(), 'value'))],
        ];
    }

    /**
     * Manual entry only carries the surface forms — tokens are split on
     * whitespace with no lemma/case/number. Such a sentence can be
     * approved and shown, but it won't produce a drill blank until its
     * tokens are annotated (AI-generated sentences arrive fully annotated).
     */
    public function toData(): SentenceData
    {
        $surfaces = preg_split('/\s+/u', trim($this->text), flags: PREG_SPLIT_NO_EMPTY) ?: [];

        $tokens = array_map(
            fn (int $position, string $surface): SentenceTokenData => new SentenceTokenData(
                position: $position,
                surface: $surface,
                lemma: null,
                case: null,
                number: null,
            ),
            array_keys($surfaces),
            $surfaces,
        );

        return new SentenceData(
            text: trim($this->text),
            translationPt: trim($this->translationPt),
            grammarFocus: $this->grammarFocus !== '' ? $this->grammarFocus : null,
            tokens: $tokens,
        );
    }
}
