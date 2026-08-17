<x-slot:mobileHeader>
    <h1 class="text-headline-lg-mobile font-bold text-primary truncate">Study</h1>
</x-slot:mobileHeader>

<div
    class="flex flex-col min-h-full"
    x-data
    x-on:keydown.window="
        if ($event.code === 'Space') { $event.preventDefault(); if (!@js($revealed)) $wire.reveal(); }
        if (@js($revealed) && ['1','2','3','4'].includes($event.key)) {
            $wire.rate({1:'again',2:'hard',3:'good',4:'easy'}[$event.key]);
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
                    <div class="absolute top-4 right-4">
                        <flux:icon.speaker-wave class="size-5 text-on-surface-variant" />
                    </div>

                    <h2
                        @class(['text-display-lg text-on-surface text-center', 'font-hebrew' => $this->card->language->isRtl()])
                        dir="{{ $this->card->language->isRtl() ? 'rtl' : 'ltr' }}"
                        lang="{{ $this->card->language->value }}"
                    >
                        {{ $this->card->word }}
                    </h2>
                    <p class="text-label-md text-on-surface-variant mt-6 opacity-60">Tap to flip</p>
                </div>

                <div class="absolute inset-0 backface-hidden rotate-x-180 rounded-xl flex flex-col items-center justify-center p-8 border-t-4 border-t-secondary bg-surface-container-high/80 backdrop-blur-md border border-outline-variant/50 shadow-lg">
                    <div class="w-full text-center space-y-4">
                        <h3 class="text-headline-lg text-primary mb-2">/{{ $this->card->transliteration }}/</h3>
                        <div class="w-12 h-1 bg-outline-variant/50 mx-auto rounded-full mb-4"></div>
                        <p class="text-body-lg text-on-surface max-w-md mx-auto leading-relaxed">{{ $this->card->definition }}</p>
                        <div class="mt-6 p-4 bg-surface-container/30 rounded-lg border border-outline-variant/20 text-body-md text-on-surface-variant">
                            <p @class(['font-hebrew' => $this->card->language->isRtl()]) dir="{{ $this->card->language->isRtl() ? 'rtl' : 'ltr' }}">{{ $this->card->example }}</p>
                            <p class="italic mt-1">"{{ $this->card->translation }}"</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if (! $revealed)
            <div class="w-full max-w-[600px] flex justify-center">
                <flux:button wire:click="reveal" variant="primary" icon:trailing="eye" class="w-full md:w-auto px-8 min-h-12 justify-center rounded-full!">
                    Show Answer
                </flux:button>
            </div>
        @else
            <div class="w-full max-w-[600px] grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
                <button type="button" wire:click="rate('again')" class="flex-1 px-6 py-4 bg-surface-container border border-error/30 text-error hover:bg-error/10 rounded-xl text-label-md font-semibold active:scale-95 transition-all flex flex-col items-center gap-1 group cursor-pointer">
                    <flux:icon.x-mark class="size-7 group-hover:-translate-y-1 transition-transform" />
                    Again ({{ $this->intervals['again'] ?? '' }})
                </button>
                <button type="button" wire:click="rate('hard')" class="flex-1 px-6 py-4 bg-surface-container border border-outline-variant text-on-surface hover:bg-surface-variant rounded-xl text-label-md font-semibold active:scale-95 transition-all flex flex-col items-center gap-1 group cursor-pointer">
                    <flux:icon.minus class="size-7 group-hover:-translate-y-1 transition-transform" />
                    Hard ({{ $this->intervals['hard'] ?? '' }})
                </button>
                <button type="button" wire:click="rate('good')" class="flex-1 px-6 py-4 bg-primary-container/20 border border-primary-container/50 text-primary hover:bg-primary-container/30 rounded-xl text-label-md font-semibold active:scale-95 transition-all flex flex-col items-center gap-1 group cursor-pointer">
                    <flux:icon.check class="size-7 group-hover:-translate-y-1 transition-transform" />
                    Good ({{ $this->intervals['good'] ?? '' }})
                </button>
                <button type="button" wire:click="rate('easy')" class="flex-1 px-6 py-4 bg-secondary/20 border border-secondary/50 text-secondary hover:bg-secondary/30 rounded-xl text-label-md font-semibold active:scale-95 transition-all flex flex-col items-center gap-1 group cursor-pointer">
                    <flux:icon.check-badge class="size-7 group-hover:-translate-y-1 transition-transform" />
                    Easy ({{ $this->intervals['easy'] ?? '' }})
                </button>
            </div>
        @endif

        <div class="mt-8 text-center text-label-sm text-outline hidden md:block">
            Press <kbd class="px-2 py-1 bg-surface-container rounded border border-outline-variant mx-1 text-on-surface">Space</kbd> to flip,
            <kbd class="px-2 py-1 bg-surface-container rounded border border-outline-variant mx-1 text-on-surface">1</kbd>–<kbd class="px-2 py-1 bg-surface-container rounded border border-outline-variant mx-1 text-on-surface">4</kbd> to rate.
        </div>
    @else
        <div class="text-center flex flex-col items-center gap-4">
            <div class="w-16 h-16 rounded-full bg-secondary-container text-on-secondary-container flex items-center justify-center">
                <flux:icon.check-badge class="size-8" />
            </div>
            <h2 class="text-headline-lg text-on-surface">Session complete</h2>
            <p class="text-body-md text-on-surface-variant">
                @if ($totalCards > 0)
                    You reviewed all {{ $totalCards }} {{ Str::plural('card', $totalCards) }} in this deck.
                @else
                    No cards are due right now. Great job staying on top of your reviews.
                @endif
            </p>
            <flux:button wire:click="restart" variant="primary" icon="arrow-path" class="mt-2">
                Study Again
            </flux:button>
        </div>
    @endif
    </div>
</div>