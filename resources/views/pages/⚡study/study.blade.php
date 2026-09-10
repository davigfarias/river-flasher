<x-slot:mobileHeader>
    <h1 class="text-headline-lg-mobile font-bold text-primary truncate">Estudar</h1>
</x-slot:mobileHeader>

@php($studyMode = $this->studyMode)
@php($isTranslation = $studyMode === \App\Enums\StudyMode::Translation)
@php($isReading = $studyMode === \App\Enums\StudyMode::Reading)

<div
    class="flex flex-col min-h-full"
    x-data="{
        transitioning: false,
        submitAnswer(result) {
            if (this.transitioning) return;
            this.transitioning = true;
            $wire.answer(result).then(() => {
                setTimeout(() => {
                    $wire.advance().then(() => { this.transitioning = false; });
                }, 500);
            });
        },
    }"
    x-on:keydown.window="
        if (transitioning) return;
        if ($event.key === 'Backspace' && @js($this->canGoBack)) { $event.preventDefault(); $wire.goBack(); return; }
        @if ($isTranslation)
            if ($event.key === 'Enter' && @js($this->translationResolved)) { $event.preventDefault(); $wire.advance(); }
        @else
            if ($event.code === 'Space') { $event.preventDefault(); if (!@js($revealed)) $wire.reveal(); }
            if (@js($revealed) && ['1','2'].includes($event.key)) {
                submitAnswer({1:'forgot',2:'remembered'}[$event.key]);
            }
        @endif
    "
>
    <div class="sticky top-0 z-20 flex items-center gap-3 px-4 md:px-6 py-3 bg-surface/90 backdrop-blur-md border-b border-outline-variant/50">
        <flux:button
            wire:click="goBack"
            x-bind:disabled="transitioning || !@js($this->canGoBack)"
            variant="ghost"
            size="sm"
            icon="arrow-uturn-left"
            title="Corrigir resposta anterior"
        />
        <flux:badge size="sm" color="zinc" :icon="$studyMode->icon()">{{ $studyMode->label() }}</flux:badge>
        <span class="text-label-sm text-on-surface-variant whitespace-nowrap truncate hidden sm:inline">{{ $deckName }}</span>
        <flux:progress :value="$this->progress" class="flex-1" />
        <span class="text-label-sm font-bold text-primary shrink-0">{{ $this->progress }}%</span>
    </div>

    <div class="flex-1 flex flex-col items-center justify-center p-4 md:p-6 lg:p-8 relative w-full max-w-[800px] mx-auto">
    @if ($this->card)
        @if ($isTranslation)
            @php($tcard = $this->translationCard)
            <div class="w-full max-w-[600px] flex flex-col gap-6" wire:key="tcard-{{ $this->card->id }}-{{ $this->translationAttempt }}">
                <div class="text-center">
                    <p class="text-label-sm text-on-surface-variant mb-3 uppercase tracking-wide">Traduza a frase</p>
                    <p
                        @class(['text-headline-lg text-on-surface leading-relaxed', 'font-hebrew' => $this->card->language->isRtl()])
                        dir="{{ $this->card->language->isRtl() ? 'rtl' : 'ltr' }}"
                        lang="{{ $this->card->language->value }}"
                    >
                        {{ $tcard->targetText }}
                    </p>
                </div>

                @if ($this->translationResolved)
                    <div @class([
                        'rounded-xl border p-5 text-center space-y-2',
                        'bg-primary-container/20 border-primary-container/50 text-primary' => $this->translationCorrect,
                        'bg-error/10 border-error/30 text-on-surface' => ! $this->translationCorrect,
                    ])>
                        <div class="flex items-center justify-center gap-2 text-label-md font-semibold">
                            <flux:icon :icon="$this->translationCorrect ? 'check-circle' : 'x-circle'" class="size-6" />
                            {{ $this->translationCorrect ? 'Você acertou!' : 'Resposta certa:' }}
                        </div>
                        @unless ($this->translationCorrect)
                            <p class="text-body-lg text-on-surface">{{ implode(' ', $tcard->correctTokens) }}</p>
                            @if ($this->card->translation)
                                <p class="text-body-sm text-on-surface-variant italic">"{{ $this->card->translation }}"</p>
                            @endif
                        @endunless
                    </div>

                    <flux:button wire:click="advance" variant="primary" icon:trailing="arrow-right" class="w-full justify-center min-h-12 rounded-full!">
                        Continuar
                    </flux:button>
                @else
                    @if ($this->translationAttempt > 0)
                        <p class="text-center text-label-md text-error font-medium">Quase. Tente montar de novo.</p>
                    @endif

                    <div
                        x-data="{
                            bank: @js(array_values($tcard->wordBank)),
                            picked: [],
                            pick(i) { this.picked.push(this.bank[i]); this.bank.splice(i, 1); },
                            unpick(i) { this.bank.push(this.picked[i]); this.picked.splice(i, 1); },
                            submit() { if (this.picked.length) $wire.checkTranslation(this.picked); },
                        }"
                    >
                        <div class="min-h-16 flex flex-wrap gap-2 content-start border-b-2 border-outline-variant/60 pb-3 mb-4">
                            <template x-for="(w, i) in picked" :key="i">
                                <button
                                    type="button"
                                    x-on:click="unpick(i)"
                                    x-text="w"
                                    class="px-3 py-2 rounded-lg bg-secondary-container/50 border border-secondary/40 text-on-surface text-body-md active:scale-95 transition-transform cursor-pointer"
                                ></button>
                            </template>
                            <span x-show="!picked.length" class="text-label-sm text-outline self-center">toque nas palavras abaixo</span>
                        </div>

                        <div class="flex flex-wrap gap-2 justify-center">
                            <template x-for="(w, i) in bank" :key="i">
                                <button
                                    type="button"
                                    x-on:click="pick(i)"
                                    x-text="w"
                                    class="px-3 py-2 rounded-lg bg-surface-container border border-outline-variant hover:bg-surface-container-high text-on-surface text-body-md active:scale-95 transition-all cursor-pointer"
                                ></button>
                            </template>
                        </div>

                        <flux:button
                            x-on:click="submit()"
                            x-bind:disabled="!picked.length"
                            variant="primary"
                            class="w-full justify-center min-h-12 rounded-full! mt-6"
                        >
                            Verificar
                        </flux:button>
                    </div>
                @endif
            </div>
        @else
            <div class="w-full max-w-[600px] h-[400px] md:h-[480px] [perspective:1000px] mb-8 cursor-pointer" x-bind:class="{ 'pointer-events-none': transitioning }" wire:click="reveal">
                <div @class(['relative w-full h-full transform-3d transition-transform duration-500 ease-in-out', 'rotate-x-180' => $revealed])>
                    <div class="absolute inset-0 backface-hidden rounded-xl flex flex-col items-center justify-center p-8 border-t-4 border-t-primary-container bg-surface-container-high/80 backdrop-blur-md border border-outline-variant/50 shadow-lg">
                        @if (! $isReading && $this->card->imageUrl())
                            <img src="{{ $this->card->imageUrl() }}" alt="" class="max-h-60 md:max-h-72 rounded-lg object-contain">
                        @else
                            @if ($this->card->pos)
                                <div class="absolute top-4 left-4 flex items-center gap-1 px-2 py-1 bg-surface-variant/50 rounded-md border border-outline-variant/30">
                                    <flux:icon.tag class="size-3.5 text-tertiary" />
                                    <span class="text-label-sm text-tertiary">{{ $this->card->pos }}</span>
                                </div>
                            @endif
                            <h2
                                @class(['text-display-lg text-on-surface text-center', 'font-hebrew' => $this->card->language->isRtl()])
                                dir="{{ $this->card->language->isRtl() ? 'rtl' : 'ltr' }}"
                                lang="{{ $this->card->language->value }}"
                            >
                                {{ $this->card->word }}
                            </h2>
                            <p class="text-label-md text-on-surface-variant mt-6 opacity-60">
                                {{ $isReading ? 'Leia em voz alta, toque para conferir' : 'Toque para virar' }}
                            </p>
                        @endif
                    </div>

                    <div class="absolute inset-0 backface-hidden rotate-x-180 rounded-xl flex flex-col items-center justify-center p-8 border-t-4 border-t-secondary bg-surface-container-high/80 backdrop-blur-md border border-outline-variant/50 shadow-lg">
                        <div class="w-full text-center space-y-4">
                            @if ($isReading)
                                <h2
                                    @class(['text-display-lg text-on-surface', 'font-hebrew' => $this->card->language->isRtl()])
                                    dir="{{ $this->card->language->isRtl() ? 'rtl' : 'ltr' }}"
                                    lang="{{ $this->card->language->value }}"
                                >
                                    {{ $this->card->word }}
                                </h2>
                                @if ($this->card->transliteration)
                                    <p class="text-headline-lg text-primary">/{{ $this->card->transliteration }}/</p>
                                @endif
                                <p class="text-body-lg text-on-surface-variant max-w-md mx-auto">{{ $this->card->definition }}</p>
                            @else
                                @if ($this->card->imageUrl())
                                    <h2
                                        @class(['text-display-lg text-on-surface mb-4', 'font-hebrew' => $this->card->language->isRtl()])
                                        dir="{{ $this->card->language->isRtl() ? 'rtl' : 'ltr' }}"
                                        lang="{{ $this->card->language->value }}"
                                    >
                                        {{ $this->card->word }}
                                    </h2>
                                @endif
                                <p class="text-headline-lg text-primary mb-2 max-w-md mx-auto leading-snug">{{ $this->card->definition }}</p>
                                @if ($this->card->transliteration)
                                    <h3 class="text-body-lg text-on-surface-variant">/{{ $this->card->transliteration }}/</h3>
                                @endif
                            @endif

                            @if ($this->card->example || $this->card->translation)
                                <div class="w-12 h-1 bg-outline-variant/50 mx-auto rounded-full mb-4"></div>
                                <div class="mt-6 p-4 bg-surface-container/30 rounded-lg border border-outline-variant/20 text-body-md text-on-surface-variant">
                                    @if ($this->card->example)
                                        <p @class(['font-hebrew' => $this->card->language->isRtl()]) dir="{{ $this->card->language->isRtl() ? 'rtl' : 'ltr' }}">{{ $this->card->example }}</p>
                                    @endif
                                    @if ($this->card->translation)
                                        <p class="italic mt-1">"{{ $this->card->translation }}"</p>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            @if (! $revealed)
                <div class="w-full max-w-[600px] flex justify-center">
                    <flux:button wire:click="reveal" x-bind:disabled="transitioning" variant="primary" icon:trailing="eye" class="w-full md:w-auto px-8 min-h-12 justify-center rounded-full!">
                        {{ $isReading ? 'Conferir leitura' : 'Mostrar resposta' }}
                    </flux:button>
                </div>
            @else
                <div class="w-full max-w-[600px] grid grid-cols-2 gap-3 sm:gap-4">
                    <button type="button" x-on:click="submitAnswer('forgot')" x-bind:disabled="transitioning" class="flex-1 px-6 py-4 bg-surface-container border border-error/30 text-error hover:bg-error/10 rounded-xl text-label-md font-semibold active:scale-95 transition-all flex flex-col items-center gap-1 group cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                        <flux:icon.x-mark class="size-7 group-hover:-translate-y-1 transition-transform" />
                        {{ $isReading ? 'Errei' : 'Não lembrei' }}
                    </button>
                    <button type="button" x-on:click="submitAnswer('remembered')" x-bind:disabled="transitioning" class="flex-1 px-6 py-4 bg-primary-container/20 border border-primary-container/50 text-primary hover:bg-primary-container/30 rounded-xl text-label-md font-semibold active:scale-95 transition-all flex flex-col items-center gap-1 group cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                        <flux:icon.check class="size-7 group-hover:-translate-y-1 transition-transform" />
                        {{ $isReading ? 'Li certo' : 'Lembrei' }}
                    </button>
                </div>
            @endif

            <div class="mt-8 text-center text-label-sm text-outline hidden md:block">
                Pressione <kbd class="px-2 py-1 bg-surface-container rounded border border-outline-variant mx-1 text-on-surface">Espaço</kbd> para virar,
                <kbd class="px-2 py-1 bg-surface-container rounded border border-outline-variant mx-1 text-on-surface">1</kbd> {{ $isReading ? 'errei' : 'não lembrei' }},
                <kbd class="px-2 py-1 bg-surface-container rounded border border-outline-variant mx-1 text-on-surface">2</kbd> {{ $isReading ? 'li certo' : 'lembrei' }}.
            </div>
        @endif
    @else
        <div class="text-center flex flex-col items-center gap-4">
            <div class="w-16 h-16 rounded-full bg-secondary-container text-on-secondary-container flex items-center justify-center">
                <flux:icon.check-badge class="size-8" />
            </div>
            <h2 class="text-headline-lg text-on-surface">Sessão concluída</h2>
            <p class="text-body-md text-on-surface-variant">
                @if ($this->totalCards > 0)
                    Você revisou {{ $this->totalCards === 1 ? 'o único cartão' : "todos os {$this->totalCards} cartões" }} deste treino.
                @elseif ($isTranslation)
                    Nenhum cartão deste treino tem frase de exemplo para traduzir ainda.
                @else
                    Não há cartões para estudar agora. Volte mais tarde.
                @endif
            </p>
            <flux:button wire:click="restart" variant="primary" icon="arrow-path" class="mt-2">
                Estudar novamente
            </flux:button>
        </div>
    @endif
    </div>
</div>
