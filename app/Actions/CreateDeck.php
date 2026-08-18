<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTO\DeckData;
use App\Models\AccessToken;
use App\Models\Deck;
use Illuminate\Support\Str;

final readonly class CreateDeck
{
    public function handle(AccessToken $token, DeckData $data): Deck
    {
        return Deck::create([
            'access_token_id' => $token->id,
            'name' => $data->name,
            'slug' => $this->uniqueSlug($token, $data->name),
        ]);
    }

    private function uniqueSlug(AccessToken $token, string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $suffix = 2;

        while (Deck::where('access_token_id', $token->id)->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
