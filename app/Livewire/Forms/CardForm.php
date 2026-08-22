<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use App\DTO\CardData;
use App\Enums\Language;
use App\Models\Card;
use App\Models\Deck;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Form;

class CardForm extends Form
{
    public string $deck = '';

    public string $language = 'el';

    public string $word = '';

    public string $transliteration = '';

    public string $definition = '';

    public string $example = '';

    public string $translation = '';

    public string $pos = '';

    public bool $markDifficult = false;

    public ?TemporaryUploadedFile $image = null;

    /**
     * Only meaningful while editing: clears an existing image without
     * replacing it. Ignored when a new `image` upload is also present.
     */
    public bool $removeImage = false;

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'deck' => ['required', 'string', Rule::in($this->availableDeckUuids())],
            'language' => ['required', new Enum(Language::class)],
            'word' => ['required', 'string', 'max:255'],
            'transliteration' => ['nullable', 'string', 'max:255'],
            'definition' => ['required', 'string', 'max:1000'],
            'example' => ['nullable', 'string', 'max:1000'],
            'translation' => ['nullable', 'string', 'max:500'],
            'pos' => ['nullable', 'string', 'max:50'],
            'image' => ['nullable', 'image', 'max:8192'],
        ];
    }

    public function fillFromCard(Card $card): void
    {
        $this->deck = $card->deck->uuid;
        $this->language = $card->language->value;
        $this->word = $card->word;
        $this->transliteration = $card->transliteration ?? '';
        $this->definition = $card->definition;
        $this->example = $card->example ?? '';
        $this->translation = $card->translation ?? '';
        $this->pos = $card->pos ?? '';
        $this->markDifficult = $card->is_difficult;
        $this->image = null;
        $this->removeImage = false;
    }

    public function toData(?string $imagePath): CardData
    {
        return new CardData(
            language: Language::from($this->language),
            word: $this->word,
            transliteration: $this->transliteration !== '' ? $this->transliteration : null,
            definition: $this->definition,
            example: $this->example !== '' ? $this->example : null,
            translation: $this->translation !== '' ? $this->translation : null,
            pos: $this->pos !== '' ? $this->pos : null,
            isDifficult: $this->markDifficult,
            imagePath: $imagePath,
        );
    }

    /**
     * @return array<int, string>
     */
    private function availableDeckUuids(): array
    {
        return Deck::query()
            ->where('access_token_id', session('access_token_id'))
            ->pluck('uuid')
            ->all();
    }
}
