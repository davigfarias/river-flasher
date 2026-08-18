<?php

declare(strict_types=1);

namespace App\Actions\Orchestrators;

use App\Actions\ApplyReviewToCard;
use App\Actions\CalculateRecallCounters;
use App\Actions\RecordReview;
use App\DTO\RecallCounters;
use App\Enums\ReviewResult;
use App\Models\Card;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final readonly class AnswerCardOrchestrator
{
    public function __construct(
        private CalculateRecallCounters $calculateRecallCounters,
        private ApplyReviewToCard $applyReviewToCard,
        private RecordReview $recordReview,
    ) {}

    public function handle(Card $card, ReviewResult $result): RecallCounters
    {
        $now = CarbonImmutable::now();

        return DB::transaction(function () use ($card, $result, $now): RecallCounters {
            $counters = $this->calculateRecallCounters->handle($card->aced_count, $card->missed_count, $result);

            $this->applyReviewToCard->handle($card, $counters, $now);
            $this->recordReview->handle($card, $result, $now);

            return $counters;
        });
    }
}
