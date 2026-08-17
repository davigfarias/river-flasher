<?php

declare(strict_types=1);

namespace App\Actions\Orchestrators;

use App\Actions\FindDueCards;
use App\DTO\StudySessionData;
use App\Models\Deck;
use Carbon\CarbonImmutable;

final readonly class StartStudySessionOrchestrator
{
    public function __construct(private FindDueCards $findDueCards) {}

    public function handle(int $accessTokenId, ?Deck $deck): StudySessionData
    {
        $cards = $this->findDueCards->handle($accessTokenId, $deck, CarbonImmutable::now());

        return new StudySessionData(
            deckName: $deck === null ? 'All decks' : $deck->name,
            cardIds: $cards->pluck('id')->all(),
        );
    }
}
