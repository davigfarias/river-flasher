<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Card;
use App\Models\Deck;
use Illuminate\Database\Eloquent\Collection;

final readonly class GetRecentCards
{
    /**
     * @return Collection<int, Card>
     */
    public function handle(Deck $deck, int $limit = 3): Collection
    {
        return $deck->cards()->latest()->limit($limit)->get();
    }
}
