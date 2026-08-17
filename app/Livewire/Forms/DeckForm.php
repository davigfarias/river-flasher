<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use App\DTO\DeckData;
use Illuminate\Validation\Rule;
use Livewire\Form;

class DeckForm extends Form
{
    /** @var array<int, string> */
    public const array ICONS = ['language', 'book-open', 'table-cells', 'sparkles', 'academic-cap', 'tag', 'fire', 'check-badge'];

    /** @var array<int, string> */
    public const array COLORS = ['text-primary', 'text-secondary', 'text-tertiary', 'text-on-surface-variant'];

    public string $name = '';

    public string $icon = 'language';

    public string $color = 'text-primary';

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'icon' => ['required', Rule::in(self::ICONS)],
            'color' => ['required', Rule::in(self::COLORS)],
        ];
    }

    public function toData(): DeckData
    {
        return new DeckData(
            name: $this->name,
            icon: $this->icon,
            color: $this->color,
        );
    }
}
