<?php

declare(strict_types=1);

namespace App\Actions;

use App\Support\Grammar\Paradigm;
use Illuminate\Support\Collection;

final readonly class ListParadigms
{
    /**
     * @return Collection<string, Paradigm> keyed by slug
     */
    public function handle(string $language = 'greek'): Collection
    {
        /** @var array<string, array<string, mixed>> $paradigms */
        $paradigms = config("grammar.{$language}.paradigms", []);

        return collect($paradigms)->map(
            fn (array $config, string $slug): Paradigm => Paradigm::fromConfig($slug, $config),
        );
    }
}
