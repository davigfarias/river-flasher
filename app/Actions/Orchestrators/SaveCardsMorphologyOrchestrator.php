<?php

declare(strict_types=1);

namespace App\Actions\Orchestrators;

use App\Actions\SetCardMorphology;
use App\DTO\CardMorphology;
use App\Models\Deck;
use Illuminate\Support\Facades\DB;

final readonly class SaveCardsMorphologyOrchestrator
{
    public function __construct(private SetCardMorphology $setCardMorphology) {}

    /**
     * Applies a batch of morphology annotations. Cards are loaded through
     * $deck, so an id that doesn't belong to the deck is silently skipped
     * rather than written.
     *
     * @param  array<int, CardMorphology>  $annotations  keyed by card id
     * @return int number of cards updated
     */
    public function handle(Deck $deck, array $annotations): int
    {
        $cards = $deck->cards()->whereIn('id', array_keys($annotations))->get();

        return DB::transaction(function () use ($cards, $annotations): int {
            foreach ($cards as $card) {
                $this->setCardMorphology->handle($card, $annotations[$card->id]);
            }

            return $cards->count();
        });
    }
}
