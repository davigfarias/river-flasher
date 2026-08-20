<?php

use App\Actions\UpdateCard;
use App\Enums\Language;
use App\Livewire\Forms\CardForm;
use App\Models\Card;
use Flux\Flux;
use Livewire\Attributes\{Locked, On};
use Livewire\Component;

new class extends Component
{
    public CardForm $form;

    #[Locked]
    public ?int $cardId = null;

    #[On('edit-card')]
    public function open(int $cardId): void
    {
        $card = $this->findCard($cardId);

        $this->cardId = $card->id;
        $this->form->fillFromCard($card);
        $this->resetValidation();

        Flux::modal('edit-card')->show();
    }

    public function save(UpdateCard $action): void
    {
        $this->form->validate();

        $card = $this->findCard($this->cardId ?? 0);

        $action->handle($card, $this->form->toData());

        Flux::toast(
            heading: 'Cartão atualizado',
            text: 'As alterações foram salvas.',
            variant: 'success',
        );

        Flux::modal('edit-card')->close();

        $this->dispatch('card-updated');
    }

    private function findCard(int $id): Card
    {
        return Card::whereHas(
            'deck',
            fn ($query) => $query->where('access_token_id', session('access_token_id')),
        )->findOrFail($id);
    }
};
?>

<flux:modal name="edit-card" class="w-full md:w-[32rem]">
    <form wire:submit="save" class="space-y-6">
        <div class="flex items-center justify-between pr-10">
            <flux:heading size="lg">Editar cartão</flux:heading>
            <flux:badge size="sm" :color="Language::from($form->language)->badgeColor()">
                {{ Language::from($form->language)->label() }}
            </flux:badge>
        </div>

        <flux:input
            wire:model="form.word"
            label="Palavra"
            dir="{{ $form->language === 'he' ? 'rtl' : 'ltr' }}"
            lang="{{ $form->language }}"
            @class(['font-hebrew!' => $form->language === 'he'])
        />

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <flux:input wire:model="form.transliteration" label="Transliteração" />
            <flux:input wire:model="form.pos" label="Classe gramatical" />
        </div>

        <flux:textarea wire:model="form.definition" label="Significado" rows="3" />

        <flux:textarea
            wire:model="form.example"
            label="Exemplo"
            rows="2"
            dir="{{ $form->language === 'he' ? 'rtl' : 'ltr' }}"
            lang="{{ $form->language }}"
            @class(['font-hebrew!' => $form->language === 'he'])
        />

        <flux:input wire:model="form.translation" label="Tradução" />

        <flux:checkbox wire:model="form.markDifficult" label="Marcar como difícil" />

        <div class="flex justify-end gap-3">
            <flux:modal.close>
                <flux:button variant="ghost">Cancelar</flux:button>
            </flux:modal.close>
            <flux:button type="submit" variant="primary">Salvar alterações</flux:button>
        </div>
    </form>
</flux:modal>
