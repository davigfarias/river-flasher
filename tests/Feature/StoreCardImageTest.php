<?php

use App\Actions\StoreCardImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Image;
use Illuminate\Support\Facades\Storage;

test('it stores the image on the default disk, downscaled and re-encoded as webp', function () {
    Storage::fake('public');

    $file = UploadedFile::fake()->image('vine.jpg', 3000, 2000);

    $path = app(StoreCardImage::class)->handle($file);

    expect($path)->toStartWith('cards/')
        ->and($path)->toEndWith('.webp');

    Storage::disk('public')->assertExists($path);

    $stored = Image::fromStorage($path, disk: 'public');

    expect($stored->width())->toBeLessThanOrEqual(1200)
        ->and($stored->height())->toBeLessThanOrEqual(1200)
        ->and($stored->mimeType())->toBe('image/webp');
});
