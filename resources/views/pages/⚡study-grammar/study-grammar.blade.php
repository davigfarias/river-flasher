<x-slot:mobileHeader>
    <h1 class="text-headline-lg-mobile font-bold text-primary truncate">Tradução — gramática</h1>
</x-slot:mobileHeader>

<div class="p-4 md:p-6 lg:p-12">
    <div class="max-w-160 mx-auto space-y-6">

        @if ($step === 'choose')
            <section class="space-y-1">
                <flux:heading size="lg">Qual declinação você quer treinar?</flux:heading>
                <flux:text class="text-on-surface-variant">{{ $deckName }}</flux:text>
            </section>

            @if (empty($options))
                <div class="bg-surface-container p-8 rounded-xl border border-outline-variant text-center space-y-4">
                    <flux:text>Nenhum cartão com declinação marcada tem frase de exemplo pronta ainda.</flux:text>
                    <flux:button :href="$this->studyUrl(filtered: false)" wire:navigate variant="primary" class="justify-center">
                        Treinar tradução sem filtro
                    </flux:button>
                </div>
            @else
                <div class="flex flex-col gap-3">
                    @foreach ($options as $slug => $label)
                        <flux:button wire:click="choose('{{ $slug }}')" variant="ghost" class="w-full justify-start">
                            {{ $label }}
                        </flux:button>
                    @endforeach
                </div>
            @endif
        @else
            @php($paradigm = $this->paradigm)

            <flux:button wire:click="back" variant="ghost" size="sm" icon="arrow-left">Voltar</flux:button>

            <section class="space-y-1">
                <flux:heading size="lg">{{ $paradigm?->label }}</flux:heading>
                <flux:text class="text-on-surface-variant">Confira a tabela antes de praticar.</flux:text>
            </section>

            <div class="overflow-x-auto rounded-xl border border-outline-variant">
                <table class="w-full text-left text-body-sm">
                    <thead class="bg-surface-container">
                        <tr>
                            <th class="p-3">Caso</th>
                            <th class="p-3">Singular</th>
                            <th class="p-3">Plural</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach (\App\Enums\GrammaticalCase::cases() as $case)
                            <tr class="border-t border-outline-variant">
                                <td class="p-3 capitalize">{{ $case->label() }}</td>
                                <td class="p-3">{{ $paradigm?->endingFor($case, \App\Enums\GrammaticalNumber::Singular) ?? '—' }}</td>
                                <td class="p-3">{{ $paradigm?->endingFor($case, \App\Enums\GrammaticalNumber::Plural) ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <flux:button :href="$this->studyUrl()" wire:navigate variant="primary" icon="play" class="w-full justify-center">
                Começar
            </flux:button>
        @endif
    </div>
</div>
