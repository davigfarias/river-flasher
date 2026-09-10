<?php

use App\Actions\GetDeckStudyModes;
use App\Enums\StudyMode;
use App\Models\Deck;
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

        unset($this->modes);

        Flux::modal('study-mode')->show();
    }

    /**
     * @return array<string, bool>
     */
    #[Computed]
    public function modes(): array
    {
        $accessTokenId = (int) session('access_token_id');

        $deckIds = $this->deckUuids === []
            ? []
            : Deck::query()
                ->whereIn('uuid', $this->deckUuids)
                ->where('access_token_id', $accessTokenId)
                ->pluck('id')
                ->all();

        return app(GetDeckStudyModes::class)->handle($accessTokenId, $deckIds);
    }

    public function url(StudyMode $mode): string
    {
        $params = ['mode' => $mode->value];

        if (count($this->deckUuids) === 1) {
            $params['deck'] = $this->deckUuids[0];
        } elseif (count($this->deckUuids) > 1) {
            $params['decks'] = implode(',', $this->deckUuids);
        }

        return route('study', $params);
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

                @if ($enabled)
                    <flux:button
                        :href="$this->url($mode)"
                        wire:navigate
                        variant="{{ $mode === \App\Enums\StudyMode::Meaning ? 'primary' : 'ghost' }}"
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
