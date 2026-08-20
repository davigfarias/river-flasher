<x-slot:mobileHeader>
    <h1 class="text-headline-lg-mobile font-bold text-primary truncate">Baralhos</h1>
</x-slot:mobileHeader>

<div class="p-4 md:p-6 lg:p-12">
    <div class="max-w-[1200px] mx-auto space-y-6">
        <section class="flex justify-between items-center gap-4">
            <h2 class="text-display-lg text-on-surface">Baralhos</h2>
            <flux:modal.trigger name="new-deck">
                <flux:button variant="primary" icon="plus" class="w-full md:w-auto justify-center">
                    Novo baralho
                </flux:button>
            </flux:modal.trigger>
        </section>

        @if ($this->decks->isEmpty())
            <div class="bg-surface-container p-12 rounded-xl border border-outline-variant shadow-sm flex flex-col items-center gap-4 text-center">
                <div class="w-16 h-16 rounded-full bg-secondary-container text-on-secondary-container flex items-center justify-center">
                    <flux:icon.rectangle-stack class="size-8" />
                </div>
                <p class="text-body-md text-on-surface-variant">Você ainda não tem nenhum baralho.</p>
                <flux:modal.trigger name="new-deck">
                    <flux:button variant="primary" icon="plus">
                        Criar primeiro baralho
                    </flux:button>
                </flux:modal.trigger>
            </div>
        @else
            <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach ($this->decks as $deck)
                    <div class="relative" wire:key="deck-{{ $deck['uuid'] }}">
                        <a
                            href="{{ route('study', ['deck' => $deck['uuid']]) }}"
                            wire:navigate
                            class="bg-surface-container hover:bg-surface-container-high transition-colors duration-300 p-6 rounded-xl border border-outline-variant shadow-sm flex flex-col gap-4 group {{ $deck['dim'] ? 'opacity-80' : '' }}"
                        >
                            <div class="flex items-start justify-between">
                                <div class="w-12 h-12 rounded-lg bg-surface-bright flex items-center justify-center border border-outline-variant text-primary">
                                    <flux:icon.rectangle-stack class="size-6" />
                                </div>
                                @if ($deck['urgent'])
                                    <div class="w-2.5 h-2.5 rounded-full bg-error"></div>
                                @endif
                            </div>

                            <div>
                                <h3 class="text-headline-sm text-on-surface group-hover:text-primary transition-colors">{{ $deck['name'] }}</h3>
                                <p class="text-label-sm text-on-surface-variant mt-1">
                                    {{ $deck['cardsCount'] }} {{ $deck['cardsCount'] === 1 ? 'cartão' : 'cartões' }}
                                </p>
                            </div>

                            <div class="mt-auto flex items-center justify-between pt-3 border-t border-outline-variant/50">
                                <span class="text-label-sm {{ $deck['dim'] ? 'text-on-surface-variant' : 'text-error' }}">{{ $deck['meta'] }}</span>
                                @if ($deck['lastReviewedAt'])
                                    <span class="text-label-sm text-on-surface-variant">{{ $deck['lastReviewedAt']->diffForHumans() }}</span>
                                @endif
                            </div>
                        </a>

                        <div class="absolute top-4 right-4 z-10 flex items-center gap-2">
                            @if ($deck['language'])
                                <flux:badge size="sm" :color="$deck['language']->badgeColor()">{{ $deck['language']->label() }}</flux:badge>
                            @endif
                            <a
                                href="{{ route('decks.show', ['deck' => $deck['uuid']]) }}"
                                wire:navigate
                                class="p-1.5 rounded-lg bg-surface-container/80 backdrop-blur-sm text-on-surface-variant hover:text-primary hover:bg-surface-bright transition-colors"
                                title="Ver cartões"
                            >
                                <flux:icon.eye class="size-5" />
                            </a>
                        </div>
                    </div>
                @endforeach
            </section>
        @endif
    </div>
</div>
