<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTO\CardData;
use App\Models\Card;
use App\Models\Deck;

final readonly class CreateCard
{
    public function handle(Deck $deck, CardData $data): Card
    {
        return $deck->cards()->create([
            'language' => $data->language,
            'pos' => $data->pos,
            'word' => $data->word,
            'transliteration' => $data->transliteration,
            'definition' => $data->definition,
            'example' => $data->example,
            'translation' => $data->translation,
            'image_path' => $data->imagePath,
            'is_difficult' => $data->isDifficult,
        ]);
    }
}
