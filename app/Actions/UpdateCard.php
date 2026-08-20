<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTO\CardData;
use App\Models\Card;

final readonly class UpdateCard
{
    public function handle(Card $card, CardData $data): Card
    {
        $card->update([
            'language' => $data->language,
            'pos' => $data->pos,
            'word' => $data->word,
            'transliteration' => $data->transliteration,
            'definition' => $data->definition,
            'example' => $data->example,
            'translation' => $data->translation,
            'is_difficult' => $data->isDifficult,
        ]);

        return $card;
    }
}
