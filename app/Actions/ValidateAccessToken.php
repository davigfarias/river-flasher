<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTO\AccessTokenDTO;
use App\Models\AccessToken;
use App\Support\Outcome;
use Illuminate\Support\Facades\Log;

final readonly class ValidateAccessToken
{
    public function handle(string $plainText): Outcome
    {
        try {
            $token = AccessToken::where('token', hash('sha256', $plainText))
                ->whereNull('revoked_at')
                ->first();

            if (! $token) {
                return Outcome::failure(message: 'Invalid code.');
            }

            $token->update(['last_used_at' => now()]);

            return Outcome::success(data: AccessTokenDTO::fromModel($token));
        } catch (\Exception $e) {
            Log::error("Error: {$e->getMessage()}");

            return Outcome::failure(message: 'Unable to validate the code.');
        }
    }
}
