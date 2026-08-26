<flux:modal name="deck-from-tag-mode" class="w-full md:w-96">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">Baralho por tema</flux:heading>
            <flux:text class="mt-2">Como você quer selecionar os cartões?</flux:text>
        </div>

        <div class="flex flex-col gap-3">
            <flux:button :href="route('decks.from-tag', ['by' => 'pos'])" wire:navigate variant="primary" class="justify-center">
                Por classe gramatical
            </flux:button>
            <flux:text class="text-center text-label-sm text-on-surface-variant -mt-2">ex.: substantivo, verbo, preposição</flux:text>

            <flux:button :href="route('decks.from-tag', ['by' => 'category'])" wire:navigate variant="primary" class="justify-center">
                Por categoria
            </flux:button>
            <flux:text class="text-center text-label-sm text-on-surface-variant -mt-2">ex.: partes do corpo, objetos de casa</flux:text>
        </div>
    </div>
</flux:modal>
