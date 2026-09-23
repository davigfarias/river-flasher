<?php

use App\Actions\GetDeckStudyModes;
use App\Enums\StudyMode;
use App\Models\{Card, Deck};
use Flux\Flux;
use Livewire\Attributes\{Computed, Locked, On};
use Livewire\Component;

new class extends Component
{
    /** @var array<int, string> */
    #[Locked]
    public array $deckUuids = [];

    public string $deckName = 'Todos os baralhos';

    /**
     * Opened from anywhere a study session can start (deck tiles, "estudar
     * selecionados", dashboard). An empty uuid list means "every deck".
     *
     * @param  array<int, string>  $deckUuids
     */
    #[On('choose-study-mode')]
    public function open(array $deckUuids = []): void
    {
        $this->deckUuids = array_values(array_filter($deckUuids));

        $accessTokenId = (int) session('access_token_id');

        $this->deckName = match (count($this->deckUuids)) {
            0 => 'Todos os baralhos',
            1 => Deck::query()
                ->where('uuid', $this->deckUuids[0])
                ->where('access_token_id', $accessTokenId)
                ->value('name') ?? 'Baralho',
            default => count($this->deckUuids).' baralhos selecionados',
        };

        unset($this->modes, $this->hasStarredCards);

        Flux::modal('study-mode')->show();
    }

    /**
     * @return array<string, bool>
     */
    #[Computed]
    public function modes(): array
    {
        $accessTokenId = (int) session('access_token_id');

        return app(GetDeckStudyModes::class)->handle($accessTokenId, $this->deckIds());
    }

    /**
     * Whether "estudar com estrela" has anything to pull from — gates the
     * split significado button the same way reading/tradução are gated.
     */
    #[Computed]
    public function hasStarredCards(): bool
    {
        $accessTokenId = (int) session('access_token_id');
        $deckIds = $this->deckIds();

        return Card::query()
            ->whereHas('deck', fn ($query) => $query->where('access_token_id', $accessTokenId))
            ->when($deckIds !== [], fn ($query) => $query->whereIn('deck_id', $deckIds))
            ->active()
            ->starred()
            ->exists();
    }

    public function url(StudyMode $mode, bool $starred = false): string
    {
        $params = ['mode' => $mode->value];

        if ($starred) {
            $params['starred'] = 1;
        }

        if (count($this->deckUuids) === 1) {
            $params['deck'] = $this->deckUuids[0];
        } elseif (count($this->deckUuids) > 1) {
            $params['decks'] = implode(',', $this->deckUuids);
        }

        return route('study', $params);
    }

    /**
     * Tradução goes through the declension picker first, instead of
     * straight into /study — see resources/views/pages/⚡study-grammar.
     */
    public function grammarUrl(): string
    {
        $params = [];

        if (count($this->deckUuids) === 1) {
            $params['deck'] = $this->deckUuids[0];
        } elseif (count($this->deckUuids) > 1) {
            $params['decks'] = implode(',', $this->deckUuids);
        }

        return route('study.grammar', $params);
    }

    /**
     * @return array<int, int>
     */
    private function deckIds(): array
    {
        if ($this->deckUuids === []) {
            return [];
        }

        $accessTokenId = (int) session('access_token_id');

        return Deck::query()
            ->whereIn('uuid', $this->deckUuids)
            ->where('access_token_id', $accessTokenId)
            ->pluck('id')
            ->all();
    }
};
?>

<flux:modal name="study-mode" class="w-full md:w-96">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">Como você quer treinar?</flux:heading>
            <flux:text class="mt-2">{{ $deckName }}</flux:text>
        </div>

        <div class="flex flex-col gap-3">
            @foreach (\App\Enums\StudyMode::cases() as $mode)
                @php($enabled = $this->modes[$mode->value] ?? false)

                @if ($enabled && $mode === \App\Enums\StudyMode::Meaning)
                    <div class="flex gap-2">
                        <flux:button
                            :href="$this->url($mode)"
                            wire:navigate
                            variant="primary"
                            :icon="$mode->icon()"
                            class="flex-1 justify-center"
                        >
                            {{ $mode->label() }}
                        </flux:button>
                        <flux:button
                            :href="$this->hasStarredCards ? $this->url($mode, starred: true) : null"
                            wire:navigate
                            variant="ghost"
                            icon="star"
                            class="flex-1 justify-center {{ $this->hasStarredCards ? 'text-amber-500' : '' }}"
                            :disabled="! $this->hasStarredCards"
                            title="{{ $this->hasStarredCards ? '' : 'Marque cartões com estrela durante o estudo para liberar' }}"
                        >
                            Com estrela
                        </flux:button>
                    </div>
                @elseif ($enabled && $mode === \App\Enums\StudyMode::Translation)
                    <flux:button
                        :href="$this->grammarUrl()"
                        wire:navigate
                        variant="ghost"
                        :icon="$mode->icon()"
                        class="w-full justify-center"
                    >
                        {{ $mode->label() }}
                    </flux:button>
                @elseif ($enabled)
                    <flux:button
                        :href="$this->url($mode)"
                        wire:navigate
                        variant="ghost"
                        :icon="$mode->icon()"
                        class="w-full justify-center"
                    >
                        {{ $mode->label() }}
                    </flux:button>
                @else
                    <flux:button variant="ghost" :icon="$mode->icon()" class="w-full justify-center" disabled>
                        {{ $mode->label() }}
                    </flux:button>
                @endif

                <flux:text class="text-center text-label-sm text-on-surface-variant -mt-2">
                    @if ($enabled)
                        {{ $mode->description() }}
                    @elseif ($mode === \App\Enums\StudyMode::Reading)
                        Adicione transliteração aos cartões para liberar
                    @else
                        Precisa de ao menos 5 cartões com frase de exemplo
                    @endif
                </flux:text>
            @endforeach
        </div>
    </div>
</flux:modal>
