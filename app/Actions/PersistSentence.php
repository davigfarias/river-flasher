<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTO\SentenceData;
use App\Enums\SentenceSource;
use App\Enums\SentenceStatus;
use App\Models\Card;
use App\Models\Deck;
use App\Models\Sentence;
use App\Support\Grammar\GreekFormComparator;

/**
 * Writes one sentence and its token rows as a single aggregate. Token
 * `card_id` is resolved from the deck's vocabulary by normalized lemma; a
 * token is a drill target only when it is a linked, inflected noun.
 */
final readonly class PersistSentence
{
    public function __construct(private GreekFormComparator $comparator) {}

    public function handle(Deck $deck, int $accessTokenId, SentenceSource $source, SentenceData $data): Sentence
    {
        $cardsByLemma = $deck->cards()
            ->get()
            ->keyBy(fn (Card $card): string => $this->comparator->normalize($card->word));

        $sentence = Sentence::create([
            'access_token_id' => $accessTokenId,
            'deck_id' => $deck->id,
            'text' => $data->text,
            'translation_pt' => $data->translationPt,
            'source' => $source,
            'status' => SentenceStatus::Pending,
            'grammar_focus' => $data->grammarFocus,
        ]);

        $sentence->tokens()->createMany(
            array_map(function ($token) use ($cardsByLemma): array {
                $cardId = $token->lemma === null
                    ? null
                    : $cardsByLemma->get($this->comparator->normalize($token->lemma))?->id;

                return [
                    'position' => $token->position,
                    'surface' => $token->surface,
                    'card_id' => $cardId,
                    'grammatical_case' => $token->case,
                    'grammatical_number' => $token->number,
                    'is_target' => $cardId !== null && $token->case !== null && $token->number !== null,
                ];
            }, $data->tokens)
        );

        return $sentence;
    }
}
