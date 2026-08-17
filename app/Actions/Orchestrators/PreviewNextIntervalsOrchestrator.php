<?php

declare(strict_types=1);

namespace App\Actions\Orchestrators;

use App\Actions\CalculateNextReview;
use App\DTO\SrsState;
use App\Enums\CardRating;
use App\Models\Card;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

final readonly class PreviewNextIntervalsOrchestrator
{
    public function __construct(private CalculateNextReview $calculateNextReview) {}

    /**
     * Runs the SM-2 calculation for all four ratings without persisting
     * anything, so the study session can show real interval labels on the
     * rating buttons ahead of time.
     *
     * @return array<string, string> keyed by CardRating::value
     */
    public function handle(Card $card): array
    {
        $state = SrsState::fromCard($card);
        $now = CarbonImmutable::now();

        return Collection::make(CardRating::cases())
            ->mapWithKeys(fn (CardRating $rating): array => [
                $rating->value => $this->calculateNextReview->handle($state, $rating, $now)->intervalLabel(),
            ])
            ->all();
    }
}
