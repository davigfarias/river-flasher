<?php

declare(strict_types=1);

namespace App\Actions;

use App\Support\Grammar\Paradigm;
use InvalidArgumentException;

final readonly class GetParadigm
{
    public function handle(string $slug, string $language = 'greek'): Paradigm
    {
        $config = config("grammar.{$language}.paradigms.{$slug}");

        if (! is_array($config)) {
            throw new InvalidArgumentException("Unknown {$language} paradigm [{$slug}].");
        }

        return Paradigm::fromConfig($slug, $config);
    }
}
