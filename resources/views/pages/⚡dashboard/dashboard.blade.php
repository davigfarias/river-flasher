<x-slot:mobileHeader>
    <h1 class="text-headline-lg-mobile font-bold text-primary truncate">Painel</h1>
</x-slot:mobileHeader>

<div class="p-4 md:p-6 lg:p-12">
    <div class="max-w-[1200px] mx-auto space-y-6">
        <section class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4 mb-8">
            <div>
                <h2 class="text-display-lg text-on-surface mb-1">{{ $this->greeting }}</h2>
                <p class="text-body-lg text-on-surface-variant">
                    @if ($this->dashboard->toReinforce > 0)
                        Você tem {{ $this->dashboard->toReinforce }} {{ $this->dashboard->toReinforce === 1 ? 'cartão para reforçar' : 'cartões para reforçar' }}.
                    @else
                        Tudo em dia. Nenhum cartão precisa de reforço agora.
                    @endif
                </p>
            </div>
            <flux:button :href="route('study')" wire:navigate variant="primary" icon="academic-cap" :disabled="$this->dashboard->totalCards === 0" class="w-full md:w-auto justify-center">
                Estudar agora
            </flux:button>
        </section>

        <section class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-surface-container hover:bg-surface-container-high transition-colors duration-300 p-6 rounded-xl border border-outline-variant shadow-sm flex flex-col justify-between group">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-label-md text-on-surface-variant">Total de cartões</h3>
                    <div class="w-8 h-8 rounded-full bg-secondary-container text-on-secondary-container flex items-center justify-center group-hover:scale-110 transition-transform">
                        <flux:icon.check-badge class="size-4" />
                    </div>
                </div>
                <div>
                    <p class="text-display-lg text-on-surface">{{ number_format($this->dashboard->totalCards) }}</p>
                    <p class="text-label-sm text-tertiary flex items-center gap-1 mt-1">
                        <flux:icon.arrow-trending-up class="size-3.5" />
                        +{{ $this->dashboard->cardsThisWeek }} esta semana
                    </p>
                </div>
            </div>

            <div class="bg-surface-container hover:bg-surface-container-high transition-colors duration-300 p-6 rounded-xl border border-outline-variant shadow-sm flex flex-col justify-between group relative overflow-hidden">
                <div class="absolute top-0 right-0 w-32 h-32 bg-primary opacity-5 blur-3xl rounded-full translate-x-1/2 -translate-y-1/2"></div>
                <div class="flex items-center justify-between mb-4 relative z-10">
                    <h3 class="text-label-md text-on-surface-variant">Revisados hoje</h3>
                    <div class="w-8 h-8 rounded-full bg-error-container text-on-error-container flex items-center justify-center group-hover:scale-110 transition-transform">
                        <flux:icon.book-open class="size-4" />
                    </div>
                </div>
                <div class="relative z-10">
                    <p class="text-display-lg text-on-surface">{{ $this->dashboard->reviewedToday }}</p>
                    <flux:progress :value="$this->dashboard->rememberedTodayPercent" class="mt-2" />
                    <p class="text-label-sm text-on-surface-variant mt-1 text-right">{{ $this->dashboard->rememberedTodayPercent }}% lembrados</p>
                </div>
            </div>

            <div class="bg-surface-container hover:bg-surface-container-high transition-colors duration-300 p-6 rounded-xl border border-outline-variant shadow-sm flex flex-col justify-between group">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-label-md text-on-surface-variant">Sequência</h3>
                    <div class="w-8 h-8 rounded-full bg-tertiary-container text-on-tertiary-container flex items-center justify-center group-hover:scale-110 transition-transform">
                        <flux:icon.fire class="size-4" />
                    </div>
                </div>
                <div>
                    <p class="text-display-lg text-on-surface">{{ $this->dashboard->streak->days }} <span class="text-headline-md text-on-surface-variant">{{ $this->dashboard->streak->days === 1 ? 'dia' : 'dias' }}</span></p>
                    <div class="flex gap-1 mt-2">
                        @foreach ($this->dashboard->streak->last7 as $active)
                            <div class="h-6 w-1 flex-1 rounded-full {{ $active ? 'bg-primary' : 'bg-surface-variant' }}"></div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        <section class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <div class="lg:col-span-2 bg-surface-container p-6 rounded-xl border border-outline-variant shadow-sm flex flex-col min-h-[360px]">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-headline-md font-semibold text-on-surface">Atividade de estudo</h3>
                    <flux:select wire:model.live="range" class="w-40" size="sm">
                        <flux:select.option value="7d">Últimos 7 dias</flux:select.option>
                        <flux:select.option value="30d">Últimos 30 dias</flux:select.option>
                        <flux:select.option value="all">Desde o início</flux:select.option>
                    </flux:select>
                </div>

                <div class="flex-1 relative flex items-end justify-between pt-8 border-b border-surface-variant pb-1">
                    <div class="absolute left-0 top-0 h-full flex flex-col justify-between text-on-surface-variant text-[10px] pb-1">
                        <span>100</span>
                        <span>75</span>
                        <span>50</span>
                        <span>25</span>
                    </div>
                    <div class="w-full pl-10 flex items-end justify-between h-full gap-2 sm:gap-4 relative z-10">
                        @foreach ($this->dashboard->activity as $day)
                            <div class="flex flex-col items-center justify-end gap-2 flex-1 self-stretch group" wire:key="activity-{{ $day['label'] }}">
                                <div class="w-full bg-primary-container hover:bg-primary transition-colors rounded-t-sm relative" style="height: {{ max($day['percent'], 2) }}%">
                                    <div class="opacity-0 group-hover:opacity-100 absolute -top-8 left-1/2 -translate-x-1/2 bg-inverse-surface text-inverse-on-surface text-[10px] px-2 py-1 rounded transition-opacity pointer-events-none">{{ $day['count'] }}</div>
                                </div>
                                <span class="text-[10px] text-on-surface-variant">{{ $day['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="flex justify-center gap-4 mt-4">
                    <div class="flex items-center gap-1">
                        <div class="w-3 h-3 rounded-full bg-secondary-container"></div>
                        <span class="text-[12px] text-on-surface-variant">Revisões</span>
                    </div>
                </div>
            </div>

            <div class="bg-surface-container p-4 lg:p-6 rounded-xl border border-outline-variant shadow-sm flex flex-col">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-[20px] font-semibold text-on-surface">Baralhos recentes</h3>
                    <flux:link :href="route('decks')" wire:navigate variant="ghost" class="text-label-sm">Ver todos</flux:link>
                </div>

                <div class="flex flex-col gap-2 overflow-y-auto pr-2 -mr-2">
                    @forelse ($this->dashboard->recentDecks as $deck)
                        <a href="{{ route('study', ['deck' => $deck['uuid']]) }}" wire:navigate wire:key="deck-{{ $deck['uuid'] }}" class="flex items-center justify-between p-2 rounded-lg hover:bg-surface-variant transition-colors group {{ $deck['dim'] ? 'opacity-70' : '' }}">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 rounded-lg bg-surface-bright flex items-center justify-center border border-outline-variant text-primary">
                                    <flux:icon.rectangle-stack class="size-5" />
                                </div>
                                <div>
                                    <h4 class="text-label-md text-on-surface group-hover:text-primary transition-colors">{{ $deck['name'] }}</h4>
                                    <p class="text-[12px] text-on-surface-variant">{{ $deck['meta'] }}</p>
                                </div>
                            </div>
                            @if ($deck['urgent'])
                                <div class="w-2 h-2 rounded-full bg-error ml-auto mr-4"></div>
                            @endif
                            <flux:icon.chevron-right class="size-4 text-on-surface-variant opacity-0 group-hover:opacity-100 transition-opacity" />
                        </a>
                    @empty
                        <p class="text-body-sm text-on-surface-variant p-2">Nenhum baralho ainda.</p>
                    @endforelse
                </div>

                <div class="mt-auto pt-4 border-t border-outline-variant">
                    <flux:modal.trigger name="new-deck">
                        <flux:button variant="ghost" icon="plus" class="w-full justify-center border border-secondary! text-secondary!">
                            Novo baralho
                        </flux:button>
                    </flux:modal.trigger>
                </div>
            </div>
        </section>
    </div>
</div>
