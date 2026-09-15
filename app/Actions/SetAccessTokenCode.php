<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\AccessToken;
use App\Support\Outcome;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Log;

final readonly class SetAccessTokenCode
{
    /**
     * Replaces the code of an existing token while keeping its id — decks and
     * reviews stay linked to it, unlike generating a brand-new token.
     */
    public function handle(int $id, string $code): Outcome
    {
        if (! preg_match('/^\d{4}$/', $code)) {
            return Outcome::failure(message: 'The code must be exactly 4 digits.');
        }

        try {
            $updated = (bool) AccessToken::where('id', $id)
                ->whereNull('revoked_at')
                ->update(['token' => hash('sha256', $code)]);

            if (! $updated) {
                return Outcome::failure(message: 'Token not found or revoked.');
            }

            return Outcome::success(message: 'Token code updated successfully.');
        } catch (UniqueConstraintViolationException) {
            return Outcome::failure(message: 'This code is already used by another token.');
        } catch (\Exception $e) {
            Log::error("Error: {$e->getMessage()}");

            return Outcome::failure(message: 'Unable to update the token code.');
        }
    }
}
