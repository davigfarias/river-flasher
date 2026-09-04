<x-slot:mobileHeader>
    <h1 class="text-headline-lg-mobile font-bold text-primary truncate">Importar CSV</h1>
</x-slot:mobileHeader>

<div class="p-4 md:p-6 lg:p-12">
    <div class="max-w-[800px] mx-auto space-y-6">
        <section class="flex flex-col gap-1">
            <h2 class="text-display-lg text-on-surface">Importar cartões via CSV</h2>
            <p class="text-body-md text-on-surface-variant">
                Envie um arquivo <code class="text-label-sm bg-surface-container px-1.5 py-0.5 rounded">.csv</code> com colunas <code class="text-label-sm bg-surface-container px-1.5 py-0.5 rounded">word</code> e <code class="text-label-sm bg-surface-container px-1.5 py-0.5 rounded">definition</code> (ou equivalentes em português) para adicionar vários cartões de uma vez.
            </p>
        </section>

        <form wire:submit="import" class="bg-surface-container p-6 rounded-xl border border-outline-variant shadow-sm flex flex-col gap-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <flux:select wire:model.live="selectedDeckId" label="Baralho de destino" placeholder="Selecione…">
                    <flux:select.option value="__new__">+ Criar novo baralho</flux:select.option>
                    @foreach ($this->deckGroups as $group)
                        <flux:select.group :label="$group['label']">
                            @foreach ($group['decks'] as $value => $label)
                                <flux:select.option :value="$value">{{ $label }}</flux:select.option>
                            @endforeach
                        </flux:select.group>
                    @endforeach
                </flux:select>

                @if ($selectedDeckId === '__new__')
                    <flux:input wire:model="newDeckName" label="Nome do novo baralho" placeholder="ex.: Vocabulário — Lição 4" />
                @endif
            </div>

            @if ($parsedCsv && $this->needsLanguageChoice)
                <flux:radio.group wire:model="importLanguage" label="Idioma dos cartões" variant="segmented">
                    <flux:radio value="el">Grego</flux:radio>
                    <flux:radio value="he">Hebraico</flux:radio>
                </flux:radio.group>
                <flux:text class="text-label-sm text-on-surface-variant -mt-4">
                    O baralho ainda não tem cartões e o CSV não define uma coluna "language" — escolha o idioma manualmente.
                </flux:text>
            @endif

            <flux:input wire:model.live="csvFile" type="file" accept=".csv,text/csv" label="Arquivo CSV" />

            <div wire:loading wire:target="csvFile" class="flex items-center gap-2 text-on-surface-variant">
                <flux:icon.arrow-path class="size-5 animate-spin" />
                <span class="text-body-sm">Lendo arquivo…</span>
            </div>

            @if ($parsedCsv)
                <div wire:loading.remove wire:target="csvFile" class="bg-surface-container-high rounded-lg border border-outline-variant p-4 space-y-3">
                    @if ($parsedCsv->missingColumns !== [])
                        <div class="flex items-start gap-2 text-error">
                            <flux:icon.exclamation-triangle class="size-5 shrink-0" />
                            <p class="text-body-sm">Coluna(s) obrigatória(s) faltando: <strong>{{ implode(', ', $parsedCsv->missingColumns) }}</strong>. Ajuste o arquivo e envie novamente.</p>
                        </div>
                    @else
                        <div class="flex flex-wrap items-center gap-x-6 gap-y-2 text-body-sm">
                            <span class="text-on-surface"><strong>{{ count($parsedCsv->rows) }}</strong> {{ count($parsedCsv->rows) === 1 ? 'cartão válido' : 'cartões válidos' }}</span>
                            @if (count($parsedCsv->rowErrors) > 0)
                                <span class="text-error"><strong>{{ count($parsedCsv->rowErrors) }}</strong> {{ count($parsedCsv->rowErrors) === 1 ? 'linha ignorada' : 'linhas ignoradas' }}</span>
                            @endif
                            @if ($parsedCsv->languagesMixed)
                                <span class="text-error flex items-center gap-1"><flux:icon.exclamation-triangle class="size-4" /> Idiomas misturados no CSV</span>
                            @elseif ($parsedCsv->detectedLanguage)
                                <flux:badge size="sm" :color="$parsedCsv->detectedLanguage->badgeColor()">{{ $parsedCsv->detectedLanguage->label() }}</flux:badge>
                            @endif
                        </div>

                        @if ($parsedCsv->rows !== [])
                            <div class="overflow-x-auto">
                                <table class="w-full text-body-sm">
                                    <thead>
                                        <tr class="text-label-sm text-on-surface-variant border-b border-outline-variant">
                                            <th class="text-left py-1 pr-4">Palavra</th>
                                            <th class="text-left py-1">Definição</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach (array_slice($parsedCsv->rows, 0, 5) as $row)
                                            <tr class="border-b border-outline-variant/50 last:border-0">
                                                <td class="py-1.5 pr-4 text-on-surface">{{ $row['word'] }}</td>
                                                <td class="py-1.5 text-on-surface-variant truncate max-w-[300px]">{{ $row['definition'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                @if (count($parsedCsv->rows) > 5)
                                    <p class="text-label-sm text-on-surface-variant mt-2">…e mais {{ count($parsedCsv->rows) - 5 }} {{ count($parsedCsv->rows) - 5 === 1 ? 'cartão' : 'cartões' }}.</p>
                                @endif
                            </div>
                        @endif
                    @endif
                </div>
            @endif

            <div class="flex justify-end gap-3 pt-2 border-t border-outline-variant">
                <flux:button :href="route('decks')" wire:navigate variant="ghost">Cancelar</flux:button>
                <flux:button
                    type="submit"
                    variant="primary"
                    icon="arrow-up-tray"
                    :disabled="! $parsedCsv || $parsedCsv->rows === [] || $parsedCsv->missingColumns !== []"
                >
                    Importar {{ $parsedCsv ? count($parsedCsv->rows) : '' }} {{ $parsedCsv && count($parsedCsv->rows) === 1 ? 'cartão' : 'cartões' }}
                </flux:button>
            </div>
        </form>
    </div>
</div>
