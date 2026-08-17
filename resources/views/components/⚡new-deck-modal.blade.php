<?php

use App\Actions\CreateDeck;
use App\Livewire\Forms\DeckForm;
use App\Models\AccessToken;
use Flux\Flux;
use Illuminate\Support\Str;
use Livewire\Component;

new class extends Component
{
    public DeckForm $form;

    public function create(CreateDeck $action): void
    {
        $this->form->validate();

        $token = AccessToken::findOrFail(session('access_token_id'));

        $deck = $action->handle($token, $this->form->toData());

        Flux::toast(
            heading: 'Deck created',
            text: '"'.$deck->name.'" is ready for cards.',
            variant: 'success',
        );

        $this->form->reset();

        Flux::modal('new-deck')->close();
    }
};
?>

<flux:modal name="new-deck" class="md:w-96">
    <form wire:submit="create" class="space-y-6">
        <div>
            <flux:heading size="lg">New Deck</flux:heading>
            <flux:text class="mt-2">Give it a name and a look. Each card picks its own language.</flux:text>
        </div>

        <flux:input wire:model="form.name" label="Name" placeholder="e.g. Koine Greek — New Testament Vocab" />

        <flux:select wire:model="form.icon" label="Icon">
            @foreach (DeckForm::ICONS as $icon)
                <flux:select.option :value="$icon">{{ Str::headline($icon) }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model="form.color" label="Color">
            @foreach (DeckForm::COLORS as $color)
                <flux:select.option :value="$color">{{ Str::headline(Str::after($color, 'text-')) }}</flux:select.option>
            @endforeach
        </flux:select>

        <div class="flex justify-end gap-3">
            <flux:modal.close>
                <flux:button variant="ghost">Cancel</flux:button>
            </flux:modal.close>
            <flux:button type="submit" variant="primary">Create Deck</flux:button>
        </div>
    </form>
</flux:modal>
