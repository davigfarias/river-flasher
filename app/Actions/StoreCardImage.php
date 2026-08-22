<?php

declare(strict_types=1);

namespace App\Actions;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Image;
use RuntimeException;

final readonly class StoreCardImage
{
    /**
     * Cards are illustrated with small reference images, not photography —
     * downscaling to a 1200px bounding box and re-encoding as WebP keeps
     * each one a few dozen KB instead of whatever a phone camera produced,
     * so the bucket doesn't fill up as the deck grows.
     */
    private const int MAX_DIMENSION = 1200;

    private const int QUALITY = 75;

    public function handle(UploadedFile $file): string
    {
        $path = Image::fromUpload($file)
            ->orient()
            ->scale(width: self::MAX_DIMENSION, height: self::MAX_DIMENSION)
            ->toWebp()
            ->quality(self::QUALITY)
            ->store(path: 'cards');

        throw_if($path === false, new RuntimeException('Failed to store card image.'));

        return $path;
    }
}
