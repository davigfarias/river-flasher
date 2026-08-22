<?php

declare(strict_types=1);

namespace App\Actions;

use Illuminate\Support\Facades\Storage;

final readonly class DeleteCardImage
{
    public function handle(?string $path): void
    {
        if ($path !== null) {
            Storage::delete($path);
        }
    }
}
