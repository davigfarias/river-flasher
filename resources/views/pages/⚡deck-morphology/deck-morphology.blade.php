<x-slot:mobileHeader>
    <h1 class="text-headline-lg-mobile font-bold text-primary truncate">Morfologia — {{ $this->deck->name }}</h1>
</x-slot:mobileHeader>

<div class="p-4 md:p-6 lg:p-12">
    <form wire:submit="save" class="max-w-[1100px] mx-auto space-y-6">
        <section class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="min-w-0">
                <flux:heading size="xl" class="truncate">Anotar morfologia</flux:heading>
                <flux:text class="mt-1">
                    Radical e paradigma de cada substantivo grego. Cartões sem paradigma não entram nos drills.
                </flux:text>
            </div>
            <flux:button :href="route('decks.show', $this->deck)" wire:navigate variant="ghost" icon="arrow-left" class="shrink-0">
                Voltar ao baralho
            </flux:button>
        </section>

        <div class="flex flex-wrap items-center gap-4">
            <flux:checkbox
                wire:model.live="onlyPending"
                :label="$this->annotatedCount > 0 ? 'Só os pendentes ('.$this->annotatedCount.' já anotado(s) oculto(s))' : 'Só os pendentes'"
            />
            <flux:spacer />
            <flux:button type="submit" variant="primary" icon="check" wire:loading.attr="disabled">
                Salvar
            </flux:button>
        </div>

        @if ($this->cards->isEmpty())
            <div class="bg-surface-container p-12 rounded-xl border border-outline-variant shadow-sm text-center">
                <flux:text>
                    @if ($this->onlyPending && $this->annotatedCount > 0)
                        Todos os cartões gregos deste baralho já estão anotados.
                    @else
                        Este baralho não tem cartões gregos para anotar.
                    @endif
                </flux:text>
            </div>
        @else
            <div class="space-y-4">
                @foreach ($this->cards as $card)
                    <div wire:key="card-{{ $card->id }}" class="bg-surface-container p-5 rounded-xl border border-outline-variant shadow-sm">
                        <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_minmax(0,2fr)] gap-4 lg:gap-6">
                            <div class="min-w-0">
                                <h3 class="text-headline-sm text-on-surface" lang="el">{{ $card->word }}</h3>
                                @if ($card->transliteration)
                                    <p class="text-body-sm text-on-surface-variant">/{{ $card->transliteration }}/</p>
                                @endif
                                <p class="text-body-sm text-on-surface-variant mt-1">{{ $card->definition }}</p>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <flux:select wire:model.live="rows.{{ $card->id }}.paradigm_slug" label="Paradigma" placeholder="Selecione…">
                                    @foreach ($this->paradigmOptions as $slug => $label)
                                        <flux:select.option :value="$slug">{{ $label }}</flux:select.option>
                                    @endforeach
                                </flux:select>

                                <flux:select wire:model="rows.{{ $card->id }}.gender" label="Gênero" placeholder="—">
                                    @foreach (\App\Enums\Gender::cases() as $gender)
                                        <flux:select.option :value="$gender->value">{{ $gender->label() }}</flux:select.option>
                                    @endforeach
                                </flux:select>

                                <flux:input wire:model="rows.{{ $card->id }}.stem" label="Radical" lang="el" />

                                @if (in_array($this->rows[$card->id]['paradigm_slug'] ?? '', $this->slugsNeedingOverride, true))
                                    <flux:input wire:model="rows.{{ $card->id }}.nom_sg_override" label="Nom. sing. (irregular)" lang="el" />
                                @endif

                                @php($preview = $this->genitivePreview($card->id))
                                @if ($preview !== '')
                                    <p class="sm:col-span-2 text-body-sm text-on-surface-variant">
                                        Genitivo singular gerado: <span lang="el" class="text-on-surface font-medium">{{ $preview }}</span>
                                        <span class="text-outline">— confira o radical contra este.</span>
                                    </p>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="flex justify-end">
                <flux:button type="submit" variant="primary" icon="check" wire:loading.attr="disabled">
                    Salvar
                </flux:button>
            </div>
        @endif
    </form>
</div>
