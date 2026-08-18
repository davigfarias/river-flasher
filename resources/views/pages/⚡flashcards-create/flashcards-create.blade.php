<x-slot:header>
    <h2 class="text-headline-md font-semibold text-primary">Criar cartão</h2>
    <flux:spacer />
    <flux:button :href="route('study')" wire:navigate variant="primary" icon="play">Iniciar sessão</flux:button>
</x-slot:header>

<x-slot:mobileHeader>
    <h1 class="text-headline-lg-mobile font-bold text-primary truncate">Criar</h1>
</x-slot:mobileHeader>

<div class="p-4 md:p-12 flex justify-center items-start">
    <div class="w-full max-w-[800px] flex flex-col gap-6">
        <div class="flex flex-col gap-1">
            <h3 class="text-headline-lg-mobile md:text-headline-lg text-on-surface font-semibold tracking-tight">Nova entrada</h3>
            <p class="text-body-md text-on-surface-variant">Adicione uma nova palavra e seu significado à sua base de conhecimento.</p>
        </div>

        <form wire:submit="addToDeck" class="bg-surface-container-high rounded-xl border border-outline-variant p-6 shadow-sm flex flex-col gap-8">
            <flux:select wire:model.live="form.deck" label="Baralho de destino">
                @foreach ($this->decks as $value => $label)
                    <flux:select.option :value="$value">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:radio.group wire:model.live="form.language" label="Idioma" variant="segmented">
                <flux:radio value="el">Grego</flux:radio>
                <flux:radio value="he">Hebraico</flux:radio>
            </flux:radio.group>

            <div class="relative flex flex-col gap-6">
                <div class="relative bg-background rounded-lg p-4 border border-outline-variant focus-within:border-secondary transition-colors group">
                    <div class="absolute top-0 left-0 w-full h-1 bg-primary-container rounded-t-lg opacity-50 group-focus-within:opacity-100 transition-opacity"></div>
                    <flux:input
                        wire:model="form.word"
                        label="Palavra"
                        :placeholder="$form->language === 'he' ? 'הַקְלֵד מִלָּה בְּעִבְרִית…' : 'Digite a palavra em grego…'"
                        dir="{{ $form->language === 'he' ? 'rtl' : 'ltr' }}"
                        lang="{{ $form->language }}"
                        @class(['border-0! bg-transparent! p-0! shadow-none! text-body-lg!', 'font-hebrew!' => $form->language === 'he'])
                    />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <flux:input wire:model="form.transliteration" label="Transliteração" placeholder="ex.: agápē" />
                    <flux:input wire:model="form.pos" label="Classe gramatical" placeholder="ex.: Substantivo" list="pos-suggestions" />
                    <datalist id="pos-suggestions">
                        <option value="Substantivo"></option>
                        <option value="Verbo"></option>
                        <option value="Adjetivo"></option>
                        <option value="Advérbio"></option>
                        <option value="Pronome"></option>
                        <option value="Preposição"></option>
                        <option value="Partícula"></option>
                        <option value="Conjunção"></option>
                    </datalist>
                </div>

                <flux:textarea
                    wire:model="form.definition"
                    label="Significado"
                    placeholder="Digite o significado…"
                    rows="3"
                />

                <flux:textarea
                    wire:model="form.example"
                    label="Exemplo"
                    placeholder="Digite uma frase de exemplo…"
                    rows="2"
                    dir="{{ $form->language === 'he' ? 'rtl' : 'ltr' }}"
                    lang="{{ $form->language }}"
                    @class(['font-hebrew!' => $form->language === 'he'])
                />

                <flux:input wire:model="form.translation" label="Tradução" placeholder="Digite a tradução do exemplo…" />
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pt-2 border-t border-outline-variant">
                <flux:checkbox wire:model="form.markDifficult" label="Marcar como difícil" />

                <div class="flex gap-4">
                    <flux:button :href="route('dashboard')" wire:navigate variant="ghost" class="flex-1 sm:flex-none justify-center">Cancelar</flux:button>
                    <flux:button type="submit" variant="primary" icon="check" class="flex-1 sm:flex-none justify-center">Adicionar ao baralho</flux:button>
                </div>
            </div>
        </form>

        <div class="flex flex-col gap-2 mt-2 opacity-70">
            <h4 class="text-label-sm text-on-surface-variant uppercase tracking-wider">Adicionados recentemente em "{{ $this->decks[$form->deck] ?? '' }}"</h4>
            <div class="flex gap-2 overflow-x-auto pb-2">
                @foreach ($this->recentCards as $card)
                    <div wire:key="recent-{{ $card->id }}" class="flex-shrink-0 bg-surface-container rounded-md px-4 py-2 border border-outline-variant flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-primary-container"></span>
                        <span @class(['text-body-md text-on-surface truncate max-w-[150px]', 'font-hebrew' => $card->language->isRtl()])>{{ $card->word }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>