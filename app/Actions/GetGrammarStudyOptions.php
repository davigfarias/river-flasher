<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\Language;
use App\Models\Card;
use Illuminate\Support\Collection;

/**
 * Which declensions (paradigms) have at least one card ready for a tradução
 * session scoped to just that paradigm. Same base eligibility as
 * FindCardsToStudy's translation branch (active, example+translation
 * filled), just grouped by paradigm_slug instead of pooled together — a
 * filtered session naturally has a smaller pool, so there's no minimum here
 * the way GetDeckStudyModes gates the mode overall. Hebrew has no paradigms
 * yet (config/grammar/hebrew.php is a Fase 2 stub), so this only ever
 * returns Greek results, same as the morphology screen.
 */
final readonly class GetGrammarStudyOptions
{
    /**
     * @param  array<int, int>  $deckIds  empty means "every deck the token owns"
     * @return Collection<string, int> card count keyed by paradigm_slug
     */
    public function handle(int $accessTokenId, array $deckIds = []): Collection
    {
        return Card::query()
            ->whereHas('deck', fn ($query) => $query->where('access_token_id', $accessTokenId))
            ->when($deckIds !== [], fn ($query) => $query->whereIn('deck_id', $deckIds))
            ->active()
            ->where('language', Language::Greek)
            ->whereNotNull('paradigm_slug')
            ->whereNotNull('example')->where('example', '!=', '')
            ->whereNotNull('translation')->where('translation', '!=', '')
            ->selectRaw('paradigm_slug, count(*) as total')
            ->groupBy('paradigm_slug')
            ->pluck('total', 'paradigm_slug');
    }
}
