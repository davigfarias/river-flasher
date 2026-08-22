<?php

use App\Actions\{DeleteCardImage, StoreCardImage, UpdateCard};
use App\Enums\Language;
use App\Livewire\Forms\CardForm;
use App\Models\Card;
use Flux\Flux;
use Livewire\Attributes\{Locked, On};
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    public CardForm $form;

    #[Locked]
    public ?int $cardId = null;

    public ?string $currentImageUrl = null;

    #[On('edit-card')]
    public function open(int $cardId): void
    {
        $card = $this->findCard($cardId);

        $this->cardId = $card->id;
        $this->form->fillFromCard($card);
        $this->currentImageUrl = $card->imageUrl();
        $this->resetValidation();

        Flux::modal('edit-card')->show();
    }

    public function save(UpdateCard $action, StoreCardImage $storeCardImage, DeleteCardImage $deleteCardImage): void
    {
        $this->form->validate();

        $card = $this->findCard($this->cardId ?? 0);

        $imagePath = $card->image_path;

        if ($this->form->image) {
            $deleteCardImage->handle($card->image_path);
            $imagePath = $storeCardImage->handle($this->form->image);
        } elseif ($this->form->removeImage) {
            $deleteCardImage->handle($card->image_path);
            $imagePath = null;
        }

        $action->handle($card, $this->form->toData($imagePath));

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

        <div class="space-y-2">
            <flux:input wire:model="form.image" type="file" accept="image/*" label="Imagem (opcional)" />

            @if ($form->image)
                <img src="{{ $form->image->temporaryUrl() }}" alt="Pré-visualização" class="h-24 rounded-lg border border-outline-variant object-cover">
            @elseif ($currentImageUrl && ! $form->removeImage)
                <div class="flex items-center gap-3">
                    <img src="{{ $currentImageUrl }}" alt="Imagem atual" class="h-24 rounded-lg border border-outline-variant object-cover">
                    <flux:checkbox wire:model="form.removeImage" label="Remover imagem" />
                </div>
            @endif
        </div>

        <flux:checkbox wire:model="form.markDifficult" label="Marcar como difícil" />

        <div class="flex justify-end gap-3">
            <flux:modal.close>
                <flux:button variant="ghost">Cancelar</flux:button>
            </flux:modal.close>
            <flux:button type="submit" variant="primary">Salvar alterações</flux:button>
        </div>
    </form>
</flux:modal>
