<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Deck;

final readonly class DeleteDeck
{
    public function __construct(private DeleteCardImage $deleteCardImage) {}

    /**
     * Deletes a deck and everything the DB cascade doesn't reach on its own:
     * `cards`/`reviews`/`translation_exercises` cascade at the FK level, but
     * the cards' image files on disk don't — those are removed explicitly
     * first, or they'd be left orphaned in storage.
     */
    public function handle(Deck $deck): void
    {
        $deck->cards()
            ->whereNotNull('image_path')
            ->pluck('image_path')
            ->each(fn (string $path) => $this->deleteCardImage->handle($path));

        $deck->delete();
    }
}
