<?php

use App\Actions\GetDecksSummary;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Livewire\Attributes\{Computed, Layout, Title};
use Livewire\Component;

new #[Layout('layouts::app')] #[Title('Baralhos')] class extends Component
{
    /**
     * @return Collection<int, array{name: string, slug: string, meta: string, icon: string, color: string, urgent: bool, dim: bool, cardsCount: int, lastReviewedAt: ?CarbonImmutable}>
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
