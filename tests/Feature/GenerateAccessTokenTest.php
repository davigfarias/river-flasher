<?php

use App\Actions\GenerateAccessToken;
use App\Models\AccessToken;

test('it generates a 4-digit plaintext code and only persists its hash', function () {
    $outcome = app(GenerateAccessToken::class)->handle('some-app');

    expect($outcome->success)->toBeTrue()
        ->and($outcome->data['plainTextToken'])->toMatch('/^\d{4}$/');

    $token = AccessToken::where('name', 'some-app')->firstOrFail();

    expect($token->token)->toBe(hash('sha256', $outcome->data['plainTextToken']))
        ->and($token->toArray())->not->toHaveKey('token');
});

test('it fails gracefully when the name is already taken', function () {
    AccessToken::factory()->create(['name' => 'duplicate']);

    $outcome = app(GenerateAccessToken::class)->handle('duplicate');

    expect($outcome->success)->toBeFalse();
});
