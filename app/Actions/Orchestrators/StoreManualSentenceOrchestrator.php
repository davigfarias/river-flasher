<?php

declare(strict_types=1);

namespace App\Actions\Orchestrators;

use App\Actions\PersistSentence;
use App\DTO\SentenceData;
use App\Enums\SentenceSource;
use App\Models\Deck;
use App\Models\Sentence;
use Illuminate\Support\Facades\DB;

final readonly class StoreManualSentenceOrchestrator
{
    public function __construct(private PersistSentence $persistSentence) {}

    /**
     * Stores a hand-entered sentence as `pending`. The deterministic
     * validator is not run here — a human is entering it and a human
     * reviews it; a manual sentence is allowed to bend the rules.
     */
    public function handle(int $accessTokenId, Deck $deck, SentenceData $data): Sentence
    {
        return DB::transaction(fn (): Sentence => $this->persistSentence->handle(
            $deck,
            $accessTokenId,
            SentenceSource::Manual,
            $data,
        ));
    }
}
