@php
    use App\Enums\GrammaticalCase;
    use App\Enums\GrammaticalNumber;
@endphp

<x-slot:mobileHeader>
    <h1 class="text-headline-lg-mobile font-bold text-primary truncate">Revisar frases</h1>
</x-slot:mobileHeader>

<div class="p-4 md:p-6 lg:p-12">
    <div class="max-w-[800px] mx-auto space-y-6">
        <section class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <flux:heading size="xl">Revisar frases</flux:heading>
                <flux:text class="mt-1">{{ $this->pending->count() }} na fila.</flux:text>
            </div>
            <div class="flex gap-2">
                <flux:modal.trigger name="nova-frase">
                    <flux:button icon="pencil-square" variant="ghost">Nova frase</flux:button>
                </flux:modal.trigger>
                <flux:modal.trigger name="gerar-frases">
                    <flux:button icon="sparkles" variant="primary">Gerar frases</flux:button>
                </flux:modal.trigger>
            </div>
        </section>

        @if ($lastRun)
            <flux:callout :variant="$lastRun['accepted'] > 0 ? 'success' : 'warning'" icon="sparkles">
                <flux:callout.heading>
                    Última geração: {{ $lastRun['accepted'] }} aceitas, {{ $lastRun['rejected'] }} rejeitadas de {{ $lastRun['generated'] }}
                </flux:callout.heading>
                @if ($lastRun['reasons'])
                    <flux:callout.text>
                        <ul class="list-disc pl-4 space-y-0.5 text-body-sm">
                            @foreach ($lastRun['reasons'] as $reason)
                                <li>{{ $reason }}</li>
                            @endforeach
                        </ul>
                    </flux:callout.text>
                @endif
            </flux:callout>
        @endif

        @if ($this->current)
            @php($sentence = $this->current)
            <div class="bg-surface-container p-6 rounded-xl border border-outline-variant shadow-sm space-y-5">
                <div class="flex flex-wrap items-center gap-2">
                    @if ($sentence->deck)
                        <flux:badge size="sm" color="zinc">{{ $sentence->deck->name }}</flux:badge>
                    @endif
                    <flux:badge size="sm" color="blue">{{ $sentence->source->label() }}</flux:badge>
                    @if ($focus = GrammaticalCase::tryFrom((string) $sentence->grammar_focus))
                        <flux:badge size="sm" color="purple">foco: {{ $focus->label() }}</flux:badge>
                    @endif
                </div>

                <p class="text-headline-md text-on-surface leading-snug" lang="el">{{ $sentence->text }}</p>

                <div>
                    @if ($editingTranslation)
                        <div class="flex items-start gap-2">
                            <flux:input wire:model="translationDraft" class="flex-1" />
                            <flux:button wire:click="saveTranslation" variant="primary" size="sm" icon="check" />
                            <flux:button x-on:click="$wire.editingTranslation = false" variant="ghost" size="sm" icon="x-mark" />
                        </div>
                        @error('translationDraft') <flux:text class="text-red-500 mt-1">{{ $message }}</flux:text> @enderror
                    @else
                        <button type="button" wire:click="startEditingTranslation" class="text-body-lg text-on-surface-variant italic text-left hover:text-on-surface">
                            "{{ $sentence->translation_pt }}" <flux:icon.pencil class="size-3.5 inline opacity-50" />
                        </button>
                    @endif
                </div>

                <div class="border-t border-outline-variant/50 pt-4">
                    <flux:text class="mb-2">Tokens</flux:text>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($sentence->tokens as $token)
                            <div @class([
                                'px-2.5 py-1.5 rounded-lg border text-body-sm',
                                'border-primary bg-primary-container/20' => $token->is_target,
                                'border-outline-variant bg-surface-container-high/40' => ! $token->is_target,
                            ])>
                                <span lang="el" class="text-on-surface font-medium">{{ $token->surface }}</span>
                                @if ($token->grammatical_case && $token->grammatical_number)
                                    <span class="text-on-surface-variant text-label-sm">
                                        · {{ $token->grammatical_case->label() }} {{ $token->grammatical_number->label() }}
                                    </span>
                                @endif
                                @if ($token->card)
                                    <span class="text-outline text-label-sm" lang="el">· {{ $token->card->word }}</span>
                                @endif
                                @if ($token->is_target)
                                    <span class="text-primary text-label-sm font-semibold">· alvo</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="flex gap-3 pt-1">
                    <flux:button wire:click="reject" variant="danger" icon="x-mark" class="flex-1 justify-center">Rejeitar</flux:button>
                    <flux:button wire:click="approve" variant="primary" icon="check" class="flex-1 justify-center">Aprovar</flux:button>
                </div>
            </div>
        @else
            <div class="bg-surface-container p-12 rounded-xl border border-outline-variant shadow-sm flex flex-col items-center gap-4 text-center">
                <div class="w-16 h-16 rounded-full bg-secondary-container text-on-secondary-container flex items-center justify-center">
                    <flux:icon.check-badge class="size-8" />
                </div>
                <flux:text>Nada na fila. Gere frases ou adicione uma manualmente.</flux:text>
            </div>
        @endif
    </div>

    {{-- Gerar frases --}}
    <flux:modal name="gerar-frases" class="w-full md:w-96">
        <form wire:submit="generate" class="space-y-5">
            <flux:heading size="lg">Gerar frases com IA</flux:heading>
            <flux:text>Faz algumas chamadas ao modelo, valida a morfologia de cada frase e guarda as boas como pendentes.</flux:text>

            <flux:select wire:model="genDeck" label="Baralho" placeholder="Selecione…">
                @foreach ($this->decks as $uuid => $name)
                    <flux:select.option :value="$uuid">{{ $name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model="genCase" label="Caso alvo">
                @foreach (GrammaticalCase::cases() as $case)
                    <flux:select.option :value="$case->value">{{ ucfirst($case->label()) }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:input type="number" wire:model="genCalls" label="Chamadas ao modelo" min="1" max="5" />

            <div class="flex justify-end gap-3">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancelar</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="generate">Gerar</span>
                    <span wire:loading wire:target="generate">Gerando frases…</span>
                </flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Nova frase manual --}}
    <flux:modal name="nova-frase" class="w-full md:w-[32rem]">
        <form wire:submit="storeManual" class="space-y-5">
            <flux:heading size="lg">Nova frase</flux:heading>

            <flux:select wire:model="manualDeck" label="Baralho" placeholder="Selecione…">
                @foreach ($this->decks as $uuid => $name)
                    <flux:select.option :value="$uuid">{{ $name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:input wire:model="form.text" label="Frase (grego)" lang="el" />
            <flux:input wire:model="form.translationPt" label="Tradução (português)" />

            <flux:select wire:model="form.grammarFocus" label="Foco gramatical (opcional)" placeholder="—">
                @foreach (GrammaticalCase::cases() as $case)
                    <flux:select.option :value="$case->value">{{ ucfirst($case->label()) }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:text class="text-label-sm">
                Os tokens são separados por espaço, sem análise. A frase pode ser aprovada, mas só vira
                exercício depois que os tokens forem anotados.
            </flux:text>

            <div class="flex justify-end gap-3">
                <flux:modal.close>
                    <flux:button variant="ghost">Cancelar</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary">Adicionar</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
