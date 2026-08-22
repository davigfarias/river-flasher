<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\Language;
use App\Models\Card;
use Illuminate\Database\Eloquent\Collection;

final readonly class FindCardsByTag
{
    /**
     * @return Collection<int, Card>
     */
    public function handle(int $accessTokenId, Language $language, string $tag): Collection
    {
        return Card::query()
            ->with('deck')
            ->whereHas('deck', fn ($query) => $query->where('access_token_id', $accessTokenId))
            ->where('language', $language)
            ->where('pos', $tag)
            ->orderBy('word')
            ->get();
    }
}
