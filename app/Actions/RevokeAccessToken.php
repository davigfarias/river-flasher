<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\AccessToken;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class RevokeAccessToken
{
    public function handle(int $id): Outcome
    {
        try {
            $revoked = AccessToken::where('id', $id)
                ->whereNull('revoked_at')
                ->update(['revoked_at' => now()]);

            if (! $revoked) {
                return Outcome::failure(message: 'Token not found or already revoked.');
            }

            return Outcome::success(message: 'Token revoked successfully.');
        } catch (\Exception $e) {
            Log::error("Error: {$e->getMessage()}");

            return Outcome::failure(message: 'Unable to revoke the token.');
        }
    }
}
