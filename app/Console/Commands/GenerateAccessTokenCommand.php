<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\GenerateAccessToken;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('token:generate {name : Name/label for the token, shared across every river app}')]
#[Description('Generates a new 4-digit access token')]
class GenerateAccessTokenCommand extends Command
{
    public function __construct(private readonly GenerateAccessToken $action)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $outcome = $this->action->handle($this->argument('name'));

        if (! $outcome->success) {
            $this->error((string) $outcome->message);

            return self::FAILURE;
        }

        $this->info("Token generated: {$outcome->data['plainTextToken']}");
        $this->warn('Copy it now — this code will not be shown again.');

        return self::SUCCESS;
    }
}
