<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTO\AccessTokenDTO;
use App\Models\AccessToken;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class ListAccessTokens
{
    public function handle(): Outcome
    {
        try {
            $tokens = AccessToken::orderByDesc('created_at')
                ->get()
                ->map(fn (AccessToken $token): AccessTokenDTO => AccessTokenDTO::fromModel($token));

            return Outcome::noViewMessage(data: $tokens);
        } catch (\Exception $e) {
            Log::error("Error: {$e->getMessage()}");

            return Outcome::failure(message: 'Unable to list the tokens.');
        }
    }
}
