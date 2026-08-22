<x-slot:mobileHeader>
    <h1 class="text-headline-lg-mobile font-bold text-primary truncate">{{ $this->deck->name }}</h1>
</x-slot:mobileHeader>

<div class="p-4 md:p-6 lg:p-12">
    <div class="max-w-[1200px] mx-auto space-y-6">
        <section class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div x-data="{ editing: false }" x-on:deck-name-updated.window="editing = false" class="flex items-center gap-2 min-w-0">
                <h2 x-show="!editing" class="text-display-lg text-on-surface truncate">{{ $this->deck->name }}</h2>
                <flux:button x-show="!editing" x-on:click="editing = true" icon="pencil" variant="ghost" size="sm" title="Renomear baralho" />

                <form
                    x-show="editing"
                    x-cloak
                    wire:submit="updateName"
                    class="flex items-center gap-2 flex-1 min-w-0"
                >
                    <flux:input wire:model="form.name" size="sm" class="flex-1 min-w-0" />
                    <flux:button type="submit" variant="primary" size="sm" icon="check" title="Salvar" />
                    <flux:button
                        type="button"
                        variant="ghost"
                        size="sm"
                        icon="x-mark"
                        title="Cancelar"
                        x-on:click="editing = false; $wire.set('form.name', @js($this->deck->name))"
                    />
                </form>
            </div>

            <flux:button :href="route('flashcards.create')" wire:navigate variant="primary" icon="plus" class="w-full sm:w-auto justify-center">
                Adicionar cartão
            </flux:button>
        </section>

        @if ($this->inactiveCount > 0)
            <flux:checkbox
                wire:model.live="showInactive"
                :label="'Mostrar '.$this->inactiveCount.' '.($this->inactiveCount === 1 ? 'cartão desativado' : 'cartões desativados')"
            />
        @endif

        @if ($this->cards->isEmpty())
            <div class="bg-surface-container p-12 rounded-xl border border-outline-variant shadow-sm flex flex-col items-center gap-4 text-center">
                <div class="w-16 h-16 rounded-full bg-secondary-container text-on-secondary-container flex items-center justify-center">
                    <flux:icon.rectangle-stack class="size-8" />
                </div>
                @if ($this->inactiveCount > 0 && ! $this->showInactive)
                    <p class="text-body-md text-on-surface-variant">Todos os cartões deste baralho estão desativados.</p>
                @else
                    <p class="text-body-md text-on-surface-variant">Este baralho ainda não tem cartões.</p>
                    <flux:button :href="route('flashcards.create')" wire:navigate variant="primary" icon="plus">
                        Adicionar o primeiro cartão
                    </flux:button>
                @endif
            </div>
        @else
            <flux:checkbox.group wire:model.live="selectedCardIds">
                <div class="flex flex-wrap items-center gap-3">
                    <flux:checkbox.all label="Selecionar todos" />

                    @if (count($selectedCardIds) > 0)
                        <span class="text-label-sm text-on-surface-variant">{{ count($selectedCardIds) }} selecionado(s)</span>

                        <flux:button size="sm" variant="ghost" icon="eye" wire:click="activateSelected">
                            Ativar selecionados
                        </flux:button>

                        <flux:modal.trigger name="confirm-deactivate-selected">
                            <flux:button size="sm" variant="danger" icon="eye-slash">
                                Desativar selecionados
                            </flux:button>
                        </flux:modal.trigger>
                    @endif
                </div>

                <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mt-4">
                    @foreach ($this->cards as $card)
                        <div wire:key="card-{{ $card->id }}" @class(['group bg-surface-container p-5 rounded-xl border border-outline-variant shadow-sm flex flex-col gap-3', 'opacity-60' => ! $card->is_active])>
                            <div class="flex items-start justify-between -mb-1">
                                <flux:checkbox value="{{ $card->id }}" />

                                <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 max-md:opacity-100 transition-opacity">
                                    <flux:button
                                        :icon="$card->is_active ? 'eye-slash' : 'eye'"
                                        size="sm"
                                        variant="subtle"
                                        wire:click="toggleCardActive({{ $card->id }})"
                                        title="{{ $card->is_active ? 'Desativar cartão' : 'Ativar cartão' }}"
                                    />
                                    <flux:button
                                        icon="pencil"
                                        size="sm"
                                        variant="subtle"
                                        wire:click="$dispatch('edit-card', { cardId: {{ $card->id }} })"
                                        title="Editar cartão"
                                    />
                                </div>
                            </div>

                            @if (! $card->is_active)
                                <flux:badge size="sm" color="zinc" class="self-start">Desativado</flux:badge>
                            @endif

                            @if ($card->pos)
                                <span class="text-label-sm text-tertiary uppercase tracking-wide">{{ $card->pos }}</span>
                            @endif

                            <h3
                                @class(['text-headline-sm text-on-surface', 'font-hebrew' => $card->language->isRtl()])
                                dir="{{ $card->language->isRtl() ? 'rtl' : 'ltr' }}"
                                lang="{{ $card->language->value }}"
                            >
                                {{ $card->word }}
                            </h3>

                            @if ($card->transliteration)
                                <p class="text-body-sm text-on-surface-variant -mt-2">/{{ $card->transliteration }}/</p>
                            @endif

                            <p class="text-body-md text-on-surface">{{ $card->definition }}</p>

                            @if ($card->example || $card->translation)
                                <div class="mt-1 p-3 bg-surface-container-high/50 rounded-lg border border-outline-variant/30 text-body-sm text-on-surface-variant">
                                    @if ($card->example)
                                        <p @class(['font-hebrew' => $card->language->isRtl()]) dir="{{ $card->language->isRtl() ? 'rtl' : 'ltr' }}">{{ $card->example }}</p>
                                    @endif
                                    @if ($card->translation)
                                        <p class="italic mt-1">"{{ $card->translation }}"</p>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endforeach
                </section>
            </flux:checkbox.group>
        @endif
    </div>

    <flux:modal name="confirm-deactivate-selected" class="w-full md:w-96">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Desativar {{ count($selectedCardIds) }} {{ count($selectedCardIds) === 1 ? 'cartão' : 'cartões' }}?</flux:heading>
                <flux:text class="mt-2">Eles somem da lista e param de aparecer no estudo até você reativá-los.</flux:text>
            </div>

            <div class="flex justify-end gap-3">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancelar</flux:button>
                </flux:modal.close>
                <flux:button variant="danger" wire:click="deactivateSelected">Desativar</flux:button>
            </div>
        </div>
    </flux:modal>

    <livewire:edit-card-modal />
</div>
