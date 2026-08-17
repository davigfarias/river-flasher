<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\RevokeAccessToken;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;

#[Signature('token:revoke {id : ID of the token to revoke} {--force : Revoke without asking for confirmation}')]
#[Description('Revokes an access token')]
class RevokeAccessTokenCommand extends Command
{
    use ConfirmableTrait;

    public function __construct(private readonly RevokeAccessToken $action)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        if (! $this->confirmToProceed('Are you sure you want to revoke this token?')) {
            return self::FAILURE;
        }

        $outcome = $this->action->handle((int) $this->argument('id'));

        if (! $outcome->success) {
            $this->error((string) $outcome->message);

            return self::FAILURE;
        }

        $this->info((string) $outcome->message);

        return self::SUCCESS;
    }
}
