<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\Language;
use App\Models\Card;
use Illuminate\Support\Collection;

final readonly class GetAvailableCategories
{
    /**
     * Distinct "category" values in use for the token, scoped to a language
     * since a deck can only ever hold cards from a single language.
     *
     * @return Collection<int, string>
     */
    public function handle(int $accessTokenId, Language $language): Collection
    {
        return Card::query()
            ->whereHas('deck', fn ($query) => $query->where('access_token_id', $accessTokenId))
            ->where('language', $language)
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');
    }
}
