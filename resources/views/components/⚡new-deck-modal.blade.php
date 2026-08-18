<?php

use App\Actions\CreateDeck;
use App\Livewire\Forms\DeckForm;
use App\Models\AccessToken;
use Flux\Flux;
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
            heading: 'Baralho criado',
            text: '"'.$deck->name.'" está pronto para receber cartões.',
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
            <flux:heading size="lg">Novo baralho</flux:heading>
            <flux:text class="mt-2">Dê um nome a ele. Cada cartão escolhe seu próprio idioma.</flux:text>
        </div>

        <flux:input wire:model="form.name" label="Nome" placeholder="ex.: Grego Koiné — Vocabulário do Novo Testamento" />

        <div class="flex justify-end gap-3">
            <flux:modal.close>
                <flux:button variant="ghost">Cancelar</flux:button>
            </flux:modal.close>
            <flux:button type="submit" variant="primary">Criar baralho</flux:button>
        </div>
    </form>
</flux:modal>
