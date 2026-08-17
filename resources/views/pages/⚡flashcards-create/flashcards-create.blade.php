<x-slot:header>
    <h2 class="text-headline-md font-semibold text-primary">Create Flashcard</h2>
    <flux:spacer />
    <flux:button :href="route('study')" wire:navigate variant="primary" icon="play">Start Session</flux:button>
</x-slot:header>

<x-slot:mobileHeader>
    <h1 class="text-headline-lg-mobile font-bold text-primary truncate">Create</h1>
</x-slot:mobileHeader>

<div class="p-4 md:p-12 flex justify-center items-start">
    <div class="w-full max-w-[800px] flex flex-col gap-6">
        <div class="flex flex-col gap-1">
            <h3 class="text-headline-lg-mobile md:text-headline-lg text-on-surface font-semibold tracking-tight">New Entry</h3>
            <p class="text-body-md text-on-surface-variant">Add a new word and definition to your knowledge base.</p>
        </div>

        <form wire:submit="addToDeck" class="bg-surface-container-high rounded-xl border border-outline-variant p-6 shadow-sm flex flex-col gap-8">
            <flux:select wire:model.live="form.deck" label="Target Deck">
                @foreach ($this->decks as $value => $label)
                    <flux:select.option :value="$value">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:radio.group wire:model.live="form.language" label="Language" variant="segmented">
                <flux:radio value="el">Greek</flux:radio>
                <flux:radio value="he">Hebrew</flux:radio>
            </flux:radio.group>

            <div class="relative flex flex-col gap-6">
                <div class="relative bg-background rounded-lg p-4 border border-outline-variant focus-within:border-secondary transition-colors group">
                    <div class="absolute top-0 left-0 w-full h-1 bg-primary-container rounded-t-lg opacity-50 group-focus-within:opacity-100 transition-opacity"></div>
                    <flux:input
                        wire:model="form.word"
                        label="Word"
                        :placeholder="$form->language === 'he' ? 'הַקְלֵד מִלָּה בְּעִבְרִית…' : 'Enter the Greek word…'"
                        dir="{{ $form->language === 'he' ? 'rtl' : 'ltr' }}"
                        lang="{{ $form->language }}"
                        @class(['border-0! bg-transparent! p-0! shadow-none! text-body-lg!', 'font-hebrew!' => $form->language === 'he'])
                    />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <flux:input wire:model="form.transliteration" label="Transliteration" placeholder="e.g. agápē" />
                    <flux:input wire:model="form.pos" label="Part of speech" placeholder="e.g. Noun" list="pos-suggestions" />
                    <datalist id="pos-suggestions">
                        <option value="Noun"></option>
                        <option value="Verb"></option>
                        <option value="Adjective"></option>
                        <option value="Adverb"></option>
                        <option value="Pronoun"></option>
                        <option value="Preposition"></option>
                        <option value="Particle"></option>
                        <option value="Conjunction"></option>
                    </datalist>
                </div>

                <flux:textarea
                    wire:model="form.definition"
                    label="Definition"
                    placeholder="Enter the definition…"
                    rows="3"
                />

                <flux:textarea
                    wire:model="form.example"
                    label="Example"
                    placeholder="Enter an example sentence…"
                    rows="2"
                    dir="{{ $form->language === 'he' ? 'rtl' : 'ltr' }}"
                    lang="{{ $form->language }}"
                    @class(['font-hebrew!' => $form->language === 'he'])
                />

                <flux:input wire:model="form.translation" label="Translation" placeholder="Enter the example's translation…" />
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pt-2 border-t border-outline-variant">
                <flux:checkbox wire:model="form.markDifficult" label="Mark as difficult" />

                <div class="flex gap-4">
                    <flux:button :href="route('dashboard')" wire:navigate variant="ghost" class="flex-1 sm:flex-none justify-center">Cancel</flux:button>
                    <flux:button type="submit" variant="primary" icon="check" class="flex-1 sm:flex-none justify-center">Add to Deck</flux:button>
                </div>
            </div>
        </form>

        <div class="flex flex-col gap-2 mt-2 opacity-70">
            <h4 class="text-label-sm text-on-surface-variant uppercase tracking-wider">Recently Added to "{{ $this->decks[$form->deck] ?? '' }}"</h4>
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