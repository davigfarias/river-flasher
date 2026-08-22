<x-slot:mobileHeader>
    <h1 class="text-headline-lg-mobile font-bold text-primary truncate">Baralho por tema</h1>
</x-slot:mobileHeader>

<div class="p-4 md:p-6 lg:p-12">
    <div class="max-w-[1200px] mx-auto space-y-6">
        <section class="flex flex-col gap-1">
            <h2 class="text-display-lg text-on-surface">Criar baralho por tema</h2>
            <p class="text-body-md text-on-surface-variant">Escolha um idioma e uma classe gramatical, selecione os cartões e monte um novo baralho com eles.</p>
        </section>

        <section class="bg-surface-container p-6 rounded-xl border border-outline-variant shadow-sm flex flex-col gap-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <flux:radio.group wire:model.live="language" label="Idioma" variant="segmented">
                    <flux:radio value="el">Grego</flux:radio>
                    <flux:radio value="he">Hebraico</flux:radio>
                </flux:radio.group>

                <flux:select wire:model.live="tag" label="Tema (classe gramatical)" placeholder="Selecione um tema…">
                    @foreach ($this->tags as $option)
                        <flux:select.option :value="$option">{{ $option }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <div wire:loading wire:target="language, tag" class="flex items-center gap-2 text-on-surface-variant">
                <flux:icon.arrow-path class="size-5 animate-spin" />
                <span class="text-body-sm">Carregando cartões…</span>
            </div>

            <div wire:loading.remove wire:target="language, tag">
                @if ($this->tag === '')
                    <div class="p-8 text-center text-body-sm text-on-surface-variant">
                        Selecione um tema para ver os cartões disponíveis.
                    </div>
                @elseif ($this->cards->isEmpty())
                    <div class="p-8 text-center text-body-sm text-on-surface-variant">
                        Nenhum cartão com o tema "{{ $tag }}" neste idioma.
                    </div>
                @else
                    <div class="flex items-center justify-between">
                        <p class="text-label-sm text-on-surface-variant">
                            {{ count($selectedCardIds) }} de {{ $this->cards->count() }} selecionado(s)
                        </p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mt-4">
                        @foreach ($this->cards as $card)
                            @php $selected = in_array($card->id, $selectedCardIds, true); @endphp
                            <button
                                type="button"
                                wire:key="tag-card-{{ $card->id }}"
                                wire:click="toggleCard({{ $card->id }})"
                                @class([
                                    'text-left p-4 rounded-xl border shadow-sm flex flex-col gap-2 transition-colors cursor-pointer',
                                    'bg-primary-container/20 border-primary' => $selected,
                                    'bg-surface-container-high border-outline-variant hover:bg-surface-bright' => ! $selected,
                                ])
                            >
                                <div class="flex items-start justify-between gap-2">
                                    <h3
                                        @class(['text-headline-sm text-on-surface', 'font-hebrew' => $card->language->isRtl()])
                                        dir="{{ $card->language->isRtl() ? 'rtl' : 'ltr' }}"
                                        lang="{{ $card->language->value }}"
                                    >
                                        {{ $card->word }}
                                    </h3>
                                    <flux:icon :icon="$selected ? 'check-circle' : 'plus-circle'" :variant="$selected ? 'solid' : 'outline'" @class(['size-6 shrink-0', 'text-primary' => $selected, 'text-on-surface-variant' => ! $selected]) />
                                </div>

                                @if ($card->transliteration)
                                    <p class="text-body-sm text-on-surface-variant -mt-1">/{{ $card->transliteration }}/</p>
                                @endif

                                <p class="text-body-sm text-on-surface line-clamp-2">{{ $card->definition }}</p>

                                <div class="mt-auto pt-2 flex items-center justify-between text-label-sm text-on-surface-variant border-t border-outline-variant/50">
                                    <span class="truncate">{{ $card->deck->name }}</span>
                                    <span class="flex items-center gap-2 shrink-0">
                                        <span class="flex items-center gap-1 text-primary">
                                            <flux:icon.check class="size-3.5" />{{ $card->aced_count }}
                                        </span>
                                        <span class="flex items-center gap-1 text-error">
                                            <flux:icon.x-mark class="size-3.5" />{{ $card->missed_count }}
                                        </span>
                                    </span>
                                </div>
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

        <section class="bg-surface-container p-6 rounded-xl border border-outline-variant shadow-sm">
            <form wire:submit="create" class="flex flex-col sm:flex-row sm:items-end gap-4">
                <flux:input wire:model="deckName" label="Nome do novo baralho" placeholder="ex.: Preposições" class="flex-1" />
                <flux:button type="submit" variant="primary" icon="plus" :disabled="count($selectedCardIds) === 0" class="justify-center">
                    Criar baralho com {{ count($selectedCardIds) }} {{ count($selectedCardIds) === 1 ? 'cartão' : 'cartões' }}
                </flux:button>
            </form>
        </section>
    </div>
</div>
