<?php

declare(strict_types=1);

namespace App\Actions\Orchestrators;

use App\Actions\FindCardsToStudy;
use App\DTO\StudySessionData;
use App\Models\Deck;

final readonly class StartStudySessionOrchestrator
{
    public function __construct(private FindCardsToStudy $findCardsToStudy) {}

    public function handle(int $accessTokenId, ?Deck $deck): StudySessionData
    {
        $cards = $this->findCardsToStudy->handle($accessTokenId, $deck);

        return new StudySessionData(
            deckName: $deck === null ? 'Todos os baralhos' : $deck->name,
            cardIds: $cards->pluck('id')->all(),
        );
    }
}
