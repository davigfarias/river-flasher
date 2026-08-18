<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use App\DTO\DeckData;
use Livewire\Form;

class DeckForm extends Form
{
    public string $name = '';

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
        ];
    }

    public function toData(): DeckData
    {
        return new DeckData(
            name: $this->name,
        );
    }
}
