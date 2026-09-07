<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTO\CardMorphology;
use App\Models\Card;

final readonly class SetCardMorphology
{
    /**
     * Writes the four morphology columns of one card. Ownership of the
     * card must be verified by the caller.
     */
    public function handle(Card $card, CardMorphology $data): void
    {
        $card->update([
            'stem' => $data->stem,
            'paradigm_slug' => $data->paradigmSlug,
            'gender' => $data->gender,
            'nom_sg_override' => $data->nomSgOverride,
        ]);
    }
}
