<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\AccessToken;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class CheckAccessTokenStillValid
{
    public function handle(int $id): Outcome
    {
        try {
            $active = AccessToken::where('id', $id)
                ->whereNull('revoked_at')
                ->exists();

            if (! $active) {
                return Outcome::failure(message: 'Invalid or revoked token.');
            }

            return Outcome::noViewMessage();
        } catch (\Exception $e) {
            Log::error("Error: {$e->getMessage()}");

            return Outcome::failure(message: 'Unable to validate the token.');
        }
    }
}
