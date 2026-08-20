<?php

use App\Actions\GetDecksSummary;
use App\Enums\Language;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Livewire\Attributes\{Computed, Layout, Title};
use Livewire\Component;

new #[Layout('layouts::app')] #[Title('Baralhos')] class extends Component
{
    /**
     * @return Collection<int, array{name: string, uuid: string, meta: string, language: ?Language, urgent: bool, dim: bool, cardsCount: int, lastReviewedAt: ?CarbonImmutable}>
     */
    #[Computed]
    public function decks(): Collection
    {
        return app(GetDecksSummary::class)->handle(
            (int) session('access_token_id'),
            CarbonImmutable::now(),
        );
    }
};
