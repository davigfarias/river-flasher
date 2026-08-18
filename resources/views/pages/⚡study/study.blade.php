<x-slot:mobileHeader>
    <h1 class="text-headline-lg-mobile font-bold text-primary truncate">Estudar</h1>
</x-slot:mobileHeader>

<div
    class="flex flex-col min-h-full"
    x-data
    x-on:keydown.window="
        if ($event.code === 'Space') { $event.preventDefault(); if (!@js($revealed)) $wire.reveal(); }
        if (@js($revealed) && ['1','2'].includes($event.key)) {
            $wire.answer({1:'forgot',2:'remembered'}[$event.key]);
        }
    "
>
    <div class="sticky top-0 z-20 flex items-center gap-3 px-4 md:px-6 py-3 bg-surface/90 backdrop-blur-md border-b border-outline-variant/50">
        <span class="text-label-sm text-on-surface-variant whitespace-nowrap truncate">{{ $deckName }}</span>
        <flux:progress :value="$this->progress" class="flex-1" />
        <span class="text-label-sm font-bold text-primary shrink-0">{{ $this->progress }}%</span>
    </div>

    <div class="flex-1 flex flex-col items-center justify-center p-4 md:p-6 lg:p-8 relative w-full max-w-[800px] mx-auto">
    @if ($this->card)
        <div class="w-full max-w-[600px] h-[400px] md:h-[480px] [perspective:1000px] mb-8 cursor-pointer" wire:click="reveal">
            <div @class(['relative w-full h-full transform-3d transition-transform duration-500 ease-in-out', 'rotate-x-180' => $revealed])>
                <div class="absolute inset-0 backface-hidden rounded-xl flex flex-col items-center justify-center p-8 border-t-4 border-t-primary-container bg-surface-container-high/80 backdrop-blur-md border border-outline-variant/50 shadow-lg">
                    <div class="absolute top-4 left-4 flex items-center gap-1 px-2 py-1 bg-surface-variant/50 rounded-md border border-outline-variant/30">
                        <flux:icon.tag class="size-3.5 text-tertiary" />
                        <span class="text-label-sm text-tertiary">{{ $this->card->pos }}</span>
                    </div>
                    <h2
                        @class(['text-display-lg text-on-surface text-center', 'font-hebrew' => $this->card->language->isRtl()])
                        dir="{{ $this->card->language->isRtl() ? 'rtl' : 'ltr' }}"
                        lang="{{ $this->card->language->value }}"
                    >
                        {{ $this->card->word }}
                    </h2>
                    <p class="text-label-md text-on-surface-variant mt-6 opacity-60">Toque para virar</p>
                </div>

                <div class="absolute inset-0 backface-hidden rotate-x-180 rounded-xl flex flex-col items-center justify-center p-8 border-t-4 border-t-secondary bg-surface-container-high/80 backdrop-blur-md border border-outline-variant/50 shadow-lg">
                    <div class="w-full text-center space-y-4">
                        <p class="text-headline-lg text-primary mb-2 max-w-md mx-auto leading-snug">{{ $this->card->definition }}</p>
                        @if ($this->card->transliteration)
                            <h3 class="text-body-lg text-on-surface-variant">/{{ $this->card->transliteration }}/</h3>
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
                <flux:button wire:click="reveal" variant="primary" icon:trailing="eye" class="w-full md:w-auto px-8 min-h-12 justify-center rounded-full!">
                    Mostrar resposta
                </flux:button>
            </div>
        @else
            <div class="w-full max-w-[600px] grid grid-cols-2 gap-3 sm:gap-4">
                <button type="button" wire:click="answer('forgot')" class="flex-1 px-6 py-4 bg-surface-container border border-error/30 text-error hover:bg-error/10 rounded-xl text-label-md font-semibold active:scale-95 transition-all flex flex-col items-center gap-1 group cursor-pointer">
                    <flux:icon.x-mark class="size-7 group-hover:-translate-y-1 transition-transform" />
                    Não lembrei
                </button>
                <button type="button" wire:click="answer('remembered')" class="flex-1 px-6 py-4 bg-primary-container/20 border border-primary-container/50 text-primary hover:bg-primary-container/30 rounded-xl text-label-md font-semibold active:scale-95 transition-all flex flex-col items-center gap-1 group cursor-pointer">
                    <flux:icon.check class="size-7 group-hover:-translate-y-1 transition-transform" />
                    Lembrei
                </button>
            </div>
        @endif

        <div class="mt-8 text-center text-label-sm text-outline hidden md:block">
            Pressione <kbd class="px-2 py-1 bg-surface-container rounded border border-outline-variant mx-1 text-on-surface">Espaço</kbd> para virar,
            <kbd class="px-2 py-1 bg-surface-container rounded border border-outline-variant mx-1 text-on-surface">1</kbd> não lembrei,
            <kbd class="px-2 py-1 bg-surface-container rounded border border-outline-variant mx-1 text-on-surface">2</kbd> lembrei.
        </div>
    @else
        <div class="text-center flex flex-col items-center gap-4">
            <div class="w-16 h-16 rounded-full bg-secondary-container text-on-secondary-container flex items-center justify-center">
                <flux:icon.check-badge class="size-8" />
            </div>
            <h2 class="text-headline-lg text-on-surface">Sessão concluída</h2>
            <p class="text-body-md text-on-surface-variant">
                @if ($totalCards > 0)
                    Você revisou {{ $totalCards === 1 ? 'o único cartão' : "todos os {$totalCards} cartões" }} deste baralho.
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