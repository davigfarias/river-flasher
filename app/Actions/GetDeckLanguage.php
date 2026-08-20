<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\Language;
use App\Models\Deck;

final readonly class GetDeckLanguage
{
    /**
     * A deck's language is derived from its cards — there's no deck-level
     * column. An empty deck has no language yet. For a legacy deck that
     * mixes both (created before this rule existed), the first card
     * (by id) decides.
     */
    public function handle(Deck $deck): ?Language
    {
        return $deck->cards()->orderBy('id')->first(['id', 'language'])?->language;
    }
}
