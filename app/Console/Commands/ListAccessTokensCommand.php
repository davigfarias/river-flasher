<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\ListAccessTokens;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('token:list')]
#[Description('Lists the registered access tokens')]
class ListAccessTokensCommand extends Command
{
    public function __construct(private readonly ListAccessTokens $action)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $outcome = $this->action->handle();

        if (! $outcome->success) {
            $this->error((string) $outcome->message);

            return self::FAILURE;
        }

        $this->table(
            ['ID', 'Name', 'Last used', 'Revoked at', 'Created at'],
            $outcome->data->map(fn ($token) => [
                $token->id,
                $token->name,
                $token->lastUsedAt ?? '—',
                $token->revokedAt ?? '—',
                $token->createdAt,
            ])
        );

        return self::SUCCESS;
    }
}
