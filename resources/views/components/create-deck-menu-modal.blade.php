<flux:modal name="create-deck-menu" class="w-full md:w-96">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">Criar baralho</flux:heading>
            <flux:text class="mt-2">Como você quer começar?</flux:text>
        </div>

        <div class="flex flex-col gap-3">
            <flux:modal.trigger name="new-deck">
                <flux:modal.close>
                    <flux:button variant="primary" icon="plus" class="w-full justify-center">
                        Criar baralho
                    </flux:button>
                </flux:modal.close>
            </flux:modal.trigger>
            <flux:text class="text-center text-label-sm text-on-surface-variant -mt-2">baralho vazio, nome apenas</flux:text>

            <flux:modal.trigger name="deck-from-tag-mode">
                <flux:modal.close>
                    <flux:button variant="ghost" icon="tag" class="w-full justify-center">
                        Baralho por tema
                    </flux:button>
                </flux:modal.close>
            </flux:modal.trigger>
            <flux:text class="text-center text-label-sm text-on-surface-variant -mt-2">a partir de cartões já existentes</flux:text>

            <flux:button :href="route('decks.import-csv')" wire:navigate variant="ghost" icon="arrow-up-tray" class="w-full justify-center">
                Importar CSV
            </flux:button>
            <flux:text class="text-center text-label-sm text-on-surface-variant -mt-2">upload em massa de um arquivo .csv</flux:text>
        </div>
    </div>
</flux:modal>
