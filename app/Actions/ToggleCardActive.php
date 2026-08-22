<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Card;

final readonly class ToggleCardActive
{
    public function handle(Card $card): Card
    {
        $card->update(['is_active' => ! $card->is_active]);

        return $card;
    }
}
