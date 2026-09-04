<?php

declare(strict_types=1);

namespace App\Actions\Orchestrators;

use App\Actions\FindCardsToStudy;
use App\DTO\StudySessionData;
use App\Models\Deck;
use Illuminate\Database\Eloquent\Collection;

final readonly class StartStudySessionOrchestrator
{
    public function __construct(private FindCardsToStudy $findCardsToStudy) {}

    /**
     * An empty $decks means "every deck the token owns" (the existing
     * no-deck "study everything" session); one or more decks builds a
     * session scoped to just those — including a custom multi-deck
     * selection from the Baralhos page.
     *
     * @param  Collection<int, Deck>  $decks
     */
    public function handle(int $accessTokenId, Collection $decks): StudySessionData
    {
        $cards = $this->findCardsToStudy->handle($accessTokenId, $decks->pluck('id')->all());

        $deckName = match (true) {
            $decks->isEmpty() => 'Todos os baralhos',
            $decks->count() === 1 => $decks->first()->name,
            default => $decks->count().' baralhos selecionados',
        };

        return new StudySessionData(
            deckName: $deckName,
            cardIds: $cards->pluck('id')->all(),
        );
    }
}
