<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\Language;
use App\Models\Card;
use App\Models\Deck;
use Illuminate\Support\Collection as SupportCollection;

final readonly class GetDeckGroupsByLanguage
{
    /**
     * Decks grouped by language, for a deck-select's optgroups — a deck
     * with no cards yet has no language and would otherwise land in
     * whatever alphabetical spot its name happens to sort to, indistinguishable
     * from same-named decks in Greek or Hebrew (e.g. three "Lição 3" decks).
     *
     * @return SupportCollection<int, array{label: string, decks: SupportCollection<string, string>}>
     */
    public function handle(int $accessTokenId): SupportCollection
    {
        $decks = Deck::query()
            ->where('access_token_id', $accessTokenId)
            ->addSelect(['language' => Card::query()
                ->select('language')
                ->whereColumn('deck_id', 'decks.id')
                ->orderBy('id')
                ->limit(1),
            ])
            ->orderBy('name')
            ->get()
            ->groupBy(fn (Deck $deck) => $deck->getAttribute('language') ?? 'none');

        return collect([
            ['label' => Language::Greek->label(), 'decks' => $decks->get(Language::Greek->value, collect())->pluck('name', 'uuid')],
            ['label' => Language::Hebrew->label(), 'decks' => $decks->get(Language::Hebrew->value, collect())->pluck('name', 'uuid')],
            ['label' => 'Em criação (sem idioma ainda)', 'decks' => $decks->get('none', collect())->pluck('name', 'uuid')],
        ])->filter(fn (array $group) => $group['decks']->isNotEmpty())->values();
    }
}
