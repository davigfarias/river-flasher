<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\StudyMode;
use App\Models\Card;

/**
 * Which of the three study modes make sense for a given set of decks, based on
 * the data already entered. Significado always applies; leitura needs at least
 * one card with a transliteration; tradução needs a handful of cards carrying
 * both an example sentence and its translation.
 */
final readonly class GetDeckStudyModes
{
    private const int MIN_SENTENCE_CARDS = 5;

    /**
     * @param  array<int, int>  $deckIds  empty means "every deck the token owns"
     * @return array<string, bool> keyed by StudyMode value
     */
    public function handle(int $accessTokenId, array $deckIds = []): array
    {
        $stats = Card::query()
            ->whereHas('deck', fn ($query) => $query->where('access_token_id', $accessTokenId))
            ->when($deckIds !== [], fn ($query) => $query->whereIn('deck_id', $deckIds))
            ->active()
            ->selectRaw("
                count(*) as total,
                sum(case when transliteration is not null and transliteration != '' then 1 else 0 end) as with_transliteration,
                sum(case when example is not null and example != '' and translation is not null and translation != '' then 1 else 0 end) as with_sentence
            ")
            ->first();

        $withTransliteration = (int) ($stats->with_transliteration ?? 0);
        $withSentence = (int) ($stats->with_sentence ?? 0);

        return [
            StudyMode::Meaning->value => true,
            StudyMode::Reading->value => $withTransliteration > 0,
            StudyMode::Translation->value => $withSentence >= self::MIN_SENTENCE_CARDS,
        ];
    }
}
