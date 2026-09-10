<?php

declare(strict_types=1);

namespace App\Actions\Orchestrators;

use App\Actions\BuildTranslationWordBank;
use App\Actions\PersistTranslationExercise;
use App\Actions\TokenizeText;
use App\DTO\TranslationCardData;
use App\Models\Card;

/**
 * Assembles one tradução card: reuses the cached exercise (or parses and
 * caches it now from the card's own fields), then draws a fresh word bank of
 * distractors from the rest of the deck. No AI, no network.
 */
final readonly class PrepareTranslationCardOrchestrator
{
    public function __construct(
        private TokenizeText $tokenizeText,
        private PersistTranslationExercise $persistTranslationExercise,
        private BuildTranslationWordBank $buildTranslationWordBank,
    ) {}

    public function handle(Card $card): TranslationCardData
    {
        $exercise = $card->translationExercise;

        if ($exercise === null) {
            $exercise = $this->persistTranslationExercise->handle(
                $card,
                $this->tokenizeText->handle((string) $card->translation),
            );
        }

        /** @var array<int, string> $correctTokens */
        $correctTokens = $exercise->tokens;

        $deckCards = $card->deck->cards()
            ->active()
            ->whereNotNull('translation')
            ->where('translation', '!=', '')
            ->get();

        return new TranslationCardData(
            targetText: $exercise->target_text,
            correctTokens: $correctTokens,
            wordBank: $this->buildTranslationWordBank->handle($correctTokens, $deckCards, $card),
        );
    }
}
