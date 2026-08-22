<?php

use App\Actions\DeleteCardImage;
use Illuminate\Support\Facades\Storage;

test('it deletes the file at the given path', function () {
    Storage::fake('public');
    Storage::disk('public')->put('cards/example.webp', 'contents');

    app(DeleteCardImage::class)->handle('cards/example.webp');

    Storage::disk('public')->assertMissing('cards/example.webp');
});

test('it does nothing when given null', function () {
    Storage::fake('public');

    app(DeleteCardImage::class)->handle(null);
})->throwsNoExceptions();
