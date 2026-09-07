<x-slot:mobileHeader>
    <h1 class="text-headline-lg-mobile font-bold text-primary truncate">Praticar — {{ $deckName }}</h1>
</x-slot:mobileHeader>

<div
    class="flex flex-col min-h-full"
    x-data="{
        busy: false,
        pick(option) {
            if (this.busy || @js($answered)) return;
            this.busy = true;
            $wire.choose(option).then(() => { this.busy = false });
        },
        cont() {
            if (this.busy || ! @js($answered)) return;
            this.busy = true;
            $wire.next().then(() => { this.busy = false });
        },
    }"
    x-on:keydown.window="
        if (@js($answered) && (event.key === 'Enter' || event.key === ' ')) { event.preventDefault(); cont(); }
    "
>
    <div class="sticky top-0 z-20 flex items-center gap-3 px-4 md:px-6 py-3 bg-surface/90 backdrop-blur-md border-b border-outline-variant/50">
        <flux:button :href="route('decks.show', ['deck' => $deckUuid])" wire:navigate variant="ghost" size="sm" icon="arrow-left" />
        <span class="text-label-sm text-on-surface-variant truncate">{{ $deckName }}</span>
        <flux:progress :value="(int) round(($completed / $total) * 100)" class="flex-1" />
        <span class="text-label-sm font-bold text-primary shrink-0">{{ $completed }}/{{ $total }}</span>
    </div>

    <div class="flex-1 flex flex-col items-center justify-center p-4 md:p-6 lg:p-8 w-full max-w-[720px] mx-auto">
        @if ($this->finished)
            <div class="text-center flex flex-col items-center gap-4">
                <div class="w-16 h-16 rounded-full bg-secondary-container text-on-secondary-container flex items-center justify-center">
                    <flux:icon.check-badge class="size-8" />
                </div>
                <h2 class="text-headline-lg text-on-surface">Sessão concluída</h2>
                <p class="text-body-md text-on-surface-variant">
                    {{ $this->correctCount }} de {{ $completed }} {{ $completed === 1 ? 'exercício' : 'exercícios' }} certo(s).
                </p>
                @if ($misses)
                    <div class="text-body-sm text-on-surface-variant">
                        <p class="mb-1">Escorregou em:</p>
                        <div class="flex flex-wrap gap-2 justify-center">
                            @foreach (array_count_values($misses) as $analysis => $count)
                                <flux:badge size="sm" color="amber">{{ $analysis }}{{ $count > 1 ? " ×{$count}" : '' }}</flux:badge>
                            @endforeach
                        </div>
                    </div>
                @endif
                <flux:button wire:click="restart" variant="primary" icon="arrow-path" class="mt-2">Praticar de novo</flux:button>
            </div>
        @elseif ($item === null)
            <div class="text-center flex flex-col items-center gap-4">
                <flux:icon.rectangle-stack class="size-10 text-on-surface-variant" />
                <h2 class="text-headline-md text-on-surface">Nenhuma frase aprovada</h2>
                <p class="text-body-md text-on-surface-variant">
                    Gere e aprove frases deste baralho para poder praticar.
                </p>
                <flux:button :href="route('sentences.review')" wire:navigate variant="primary" icon="sparkles">Revisar frases</flux:button>
            </div>
        @else
            <p class="text-label-md text-on-surface-variant mb-4">Complete a frase com a forma correta.</p>

            <div class="w-full bg-surface-container-high/60 border border-outline-variant/50 rounded-xl p-6 md:p-8 mb-6 text-center">
                <p class="text-headline-md text-on-surface leading-relaxed" lang="el">
                    @foreach ($item->before as $word){{ $word }} @endforeach<span @class([
                        'inline-block min-w-24 px-3 py-0.5 rounded-md border-b-2 align-baseline',
                        'border-primary text-outline' => ! $answered,
                        'border-secondary bg-secondary-container/40 text-on-surface' => $answered && $lastCorrect,
                        'border-error bg-error/10 text-on-surface' => $answered && ! $lastCorrect,
                    ])>{{ $answered ? $chosen : '—' }}</span> @foreach ($item->after as $word){{ $word }} @endforeach
                </p>
                <p class="text-body-sm text-on-surface-variant italic mt-4">"{{ $item->translation }}"</p>
            </div>

            <div class="w-full grid grid-cols-2 gap-3">
                @foreach ($item->options as $option)
                    <button
                        type="button"
                        wire:key="opt-{{ $completed }}-{{ $loop->index }}"
                        x-on:click="pick(@js($option))"
                        x-bind:disabled="busy || @js($answered)"
                        @class([
                            'px-4 py-3 rounded-xl border text-body-lg font-medium transition-all active:scale-95 cursor-pointer disabled:cursor-default',
                            'border-outline-variant bg-surface-container hover:bg-surface-container-high' => ! $answered,
                            'border-secondary bg-secondary-container/40' => $answered && $option === $item->correctSurface,
                            'border-error bg-error/10' => $answered && $option === $chosen && $option !== $item->correctSurface,
                            'border-outline-variant bg-surface-container opacity-60' => $answered && $option !== $chosen && $option !== $item->correctSurface,
                        ])
                        lang="el"
                    >
                        {{ $option }}
                    </button>
                @endforeach
            </div>

            @if ($answered)
                <div @class([
                    'w-full mt-5 p-4 rounded-xl border flex flex-col gap-1',
                    'border-secondary bg-secondary-container/20' => $lastCorrect,
                    'border-error bg-error/10' => ! $lastCorrect,
                ])>
                    <p class="text-label-md font-semibold {{ $lastCorrect ? 'text-secondary' : 'text-error' }}">
                        {{ $lastCorrect ? 'Certo!' : 'Não é essa.' }}
                        <span class="text-on-surface-variant font-normal">
                            — {{ $item->lemma }}, {{ $item->analysis }}: <span lang="el" class="text-on-surface">{{ $item->correctSurface }}</span>
                        </span>
                    </p>
                    <flux:button x-on:click="cont()" variant="primary" size="sm" class="self-end mt-1" icon:trailing="arrow-right">
                        Continuar
                    </flux:button>
                </div>
            @endif
        @endif
    </div>
</div>
