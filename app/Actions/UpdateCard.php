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
            'category' => $data->category,
            'word' => $data->word,
            'transliteration' => $data->transliteration,
            'definition' => $data->definition,
            'example' => $data->example,
            'translation' => $data->translation,
            'image_path' => $data->imagePath,
            'is_image_hidden' => $data->isImageHidden,
            'is_difficult' => $data->isDifficult,
        ]);

        return $card;
    }
}
