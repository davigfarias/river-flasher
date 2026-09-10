<?php

use App\Actions\Orchestrators\BuildDashboardOrchestrator;
use App\DTO\DashboardData;
use App\Enums\ActivityRange;
use Livewire\Attributes\{Computed, Layout, Title};
use Livewire\Component;

new #[Layout('layouts::app')] #[Title('Painel')] class extends Component
{
    public string $range = '7d';

    #[Computed]
    public function dashboard(): DashboardData
    {
        return app(BuildDashboardOrchestrator::class)->handle(
            (int) session('access_token_id'),
            ActivityRange::from($this->range),
        );
    }

    #[Computed]
    public function greeting(): string
    {
        return match (true) {
            now()->hour < 12 => 'Bom dia.',
            now()->hour < 18 => 'Boa tarde.',
            default => 'Boa noite.',
        };
    }

    public function chooseStudyMode(?string $uuid = null): void
    {
        $this->dispatch('choose-study-mode', deckUuids: $uuid !== null ? [$uuid] : [])->to('study-mode-modal');
    }
};
