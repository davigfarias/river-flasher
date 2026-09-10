<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Card;
use Illuminate\Support\Collection;

/**
 * Builds the shuffled word bank for one tradução card: every correct
 * translation token (duplicates kept) plus a handful of distractors drawn
 * from the rest of the deck — no AI.
 *
 * Distractor pool, in priority order:
 *   1. translation words of sibling cards that share this card's category or pos
 *   2. translation words of any other card in the deck
 *   3. words from this card's own definition (a same-topic near-miss)
 *
 * Function words ("de", "que", "não"...), very short tokens and anything
 * already in the answer are filtered out so every distractor is a real trap.
 */
final readonly class BuildTranslationWordBank
{
    private const int MAX_BANK = 14;

    private const int MIN_DISTRACTORS = 3;

    /** @var array<int, string> */
    private const array STOPWORDS = [
        'a', 'o', 'e', 'de', 'da', 'do', 'das', 'dos', 'que', 'com', 'por',
        'para', 'um', 'uma', 'no', 'na', 'nos', 'nas', 'ao', 'aos', 'se',
        'em', 'os', 'as', 'não', 'sua', 'seu', 'ele', 'ela', 'foi', 'é',
    ];

    /**
     * @param  array<int, string>  $correctTokens
     * @param  Collection<int, Card>  $deckCards
     * @return array<int, string>
     */
    public function handle(array $correctTokens, Collection $deckCards, Card $currentCard): array
    {
        $answerKeys = $this->keys($correctTokens);

        $wantDistractors = max(
            self::MIN_DISTRACTORS,
            min(6, self::MAX_BANK - count($correctTokens)),
        );

        $siblings = $deckCards
            ->reject(fn (Card $card) => $card->id === $currentCard->id)
            ->sortByDesc(fn (Card $card) => $this->relatedness($card, $currentCard));

        $pool = collect();

        foreach ($siblings as $card) {
            foreach ($this->tokenize((string) $card->translation) as $token) {
                $pool->push($token);
            }
        }

        foreach ($this->tokenize((string) $currentCard->definition) as $token) {
            $pool->push($token);
        }

        $distractors = $pool
            ->filter(fn (string $token) => $this->isUsable($token, $answerKeys))
            ->unique(fn (string $token) => mb_strtolower($token))
            ->take($wantDistractors)
            ->values()
            ->all();

        $bank = [...$correctTokens, ...$distractors];

        shuffle($bank);

        return $bank;
    }

    /**
     * @param  array<int, string>  $tokens
     * @return array<string, true>
     */
    private function keys(array $tokens): array
    {
        $keys = [];

        foreach ($tokens as $token) {
            $keys[mb_strtolower($token)] = true;
        }

        return $keys;
    }

    /**
     * @param  array<string, true>  $answerKeys
     */
    private function isUsable(string $token, array $answerKeys): bool
    {
        $key = mb_strtolower($token);

        return mb_strlen($token) > 2
            && ! isset($answerKeys[$key])
            && ! in_array($key, self::STOPWORDS, true);
    }

    private function relatedness(Card $card, Card $reference): int
    {
        $score = 0;

        if ($card->category !== null && $card->category === $reference->category) {
            $score++;
        }

        if ($card->pos !== null && $card->pos === $reference->pos) {
            $score++;
        }

        return $score;
    }

    /**
     * @return array<int, string>
     */
    private function tokenize(string $text): array
    {
        $pieces = preg_split('/\s+/u', trim($text)) ?: [];

        $tokens = [];

        foreach ($pieces as $piece) {
            $clean = preg_replace('/^[\p{P}\p{S}]+|[\p{P}\p{S}]+$/u', '', $piece);

            if ($clean !== null && $clean !== '') {
                $tokens[] = $clean;
            }
        }

        return $tokens;
    }
}
