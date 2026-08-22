<?php

use App\Actions\GetDecksSummary;
use App\Enums\Language;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Livewire\Attributes\{Computed, Layout, Title};
use Livewire\Component;

new #[Layout('layouts::app')] #[Title('Baralhos')] class extends Component
{
    public bool $showGreek = true;

    public bool $showHebrew = true;

    /**
     * @return Collection<int, array{name: string, uuid: string, meta: string, language: ?Language, urgent: bool, dim: bool, cardsCount: int, lastReviewedAt: ?CarbonImmutable}>
     */
    #[Computed]
    public function decks(): Collection
    {
        return app(GetDecksSummary::class)
            ->handle((int) session('access_token_id'), CarbonImmutable::now())
            ->filter(fn (array $deck) => $this->passesLanguageFilter($deck['language']))
            ->values();
    }

    /**
     * A deck without a language yet (no cards) isn't excluded by either
     * toggle — it doesn't belong to a language to filter against.
     */
    private function passesLanguageFilter(?Language $language): bool
    {
        return match ($language) {
            null => true,
            Language::Greek => $this->showGreek,
            Language::Hebrew => $this->showHebrew,
        };
    }
};
