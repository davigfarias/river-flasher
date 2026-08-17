<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTO\AccessTokenDTO;
use App\Models\AccessToken;
use App\Support\Outcome;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Log;

final readonly class GenerateAccessToken
{
    /**
     * The 4-digit keyspace (10,000 codes) collides well within the
     * birthday bound, so a handful of retries on a unique-constraint hit
     * keeps generation reliable instead of failing on the first collision.
     */
    private const int MAX_ATTEMPTS = 5;

    /**
     * On success, `Outcome::$data` is `array{plainTextToken: string, token: AccessTokenDTO}`.
     */
    public function handle(string $name): Outcome
    {
        for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; $attempt++) {
            try {
                $plainText = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);

                $token = AccessToken::create([
                    'name' => $name,
                    'token' => hash('sha256', $plainText),
                ]);

                return Outcome::success(message: 'Token generated successfully.', data: [
                    'plainTextToken' => $plainText,
                    'token' => AccessTokenDTO::fromModel($token),
                ]);
            } catch (UniqueConstraintViolationException) {
                continue;
            } catch (\Exception $e) {
                Log::error("Error: {$e->getMessage()}");

                return Outcome::failure(message: 'Unable to generate the token.');
            }
        }

        return Outcome::failure(message: 'Unable to generate a unique code, please try again.');
    }
}
