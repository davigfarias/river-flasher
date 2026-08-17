<?php

declare(strict_types=1);

namespace App\Actions\Orchestrators;

use App\Actions\ApplyReviewToCard;
use App\Actions\CalculateNextReview;
use App\Actions\RecordReview;
use App\DTO\ReviewOutcome;
use App\DTO\SrsState;
use App\Enums\CardRating;
use App\Models\Card;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final readonly class RateCardOrchestrator
{
    public function __construct(
        private CalculateNextReview $calculateNextReview,
        private ApplyReviewToCard $applyReviewToCard,
        private RecordReview $recordReview,
    ) {}

    public function handle(Card $card, CardRating $rating): ReviewOutcome
    {
        $now = CarbonImmutable::now();

        return DB::transaction(function () use ($card, $rating, $now): ReviewOutcome {
            $outcome = $this->calculateNextReview->handle(SrsState::fromCard($card), $rating, $now);

            $this->applyReviewToCard->handle($card, $outcome, $now);
            $this->recordReview->handle($card, $rating, $outcome, $now);

            return $outcome;
        });
    }
}
