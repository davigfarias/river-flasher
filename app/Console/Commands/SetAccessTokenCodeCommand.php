<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\SetAccessTokenCode;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;

#[Signature('token:set-code {id : ID of the token} {code : New 4-digit code} {--force : Change without asking for confirmation}')]
#[Description('Sets the code of an existing access token')]
class SetAccessTokenCodeCommand extends Command
{
    use ConfirmableTrait;

    public function __construct(private readonly SetAccessTokenCode $action)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        if (! $this->confirmToProceed('Are you sure you want to change the code of this token?')) {
            return self::FAILURE;
        }

        $outcome = $this->action->handle((int) $this->argument('id'), (string) $this->argument('code'));

        if (! $outcome->success) {
            $this->error((string) $outcome->message);

            return self::FAILURE;
        }

        $this->info((string) $outcome->message);

        return self::SUCCESS;
    }
}
