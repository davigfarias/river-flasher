<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Card;

final readonly class SetCardsActiveState
{
    /**
     * Bulk-flips `is_active` for the given (already ownership-verified)
     * card ids — the multi-select counterpart to ToggleCardActive, so
     * deactivating dozens of cards doesn't mean dozens of individual clicks.
     *
     * @param  array<int, int>  $cardIds
     */
    public function handle(array $cardIds, bool $active): int
    {
        return Card::query()->whereIn('id', $cardIds)->update(['is_active' => $active]);
    }
}
