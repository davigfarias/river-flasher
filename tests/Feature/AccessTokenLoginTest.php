<?php

use App\Models\AccessToken;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

test('a valid code logs the visitor in and redirects to the dashboard', function () {
    $token = AccessToken::factory()->create([
        'token' => hash('sha256', '1234'),
    ]);

    Livewire::test('pages::auth-code')
        ->set('code', '1234')
        ->call('joinSession')
        ->assertRedirect(route('dashboard'));

    expect(session('access_token_id'))->toBe($token->id);
});

test('an invalid code shows an error and does not authenticate', function () {
    AccessToken::factory()->create(['token' => hash('sha256', '1234')]);

    Livewire::test('pages::auth-code')
        ->set('code', '9999')
        ->call('joinSession')
        ->assertNoRedirect();

    expect(session('access_token_id'))->toBeNull();
});

test('a revoked code cannot log in', function () {
    AccessToken::factory()->revoked()->create(['token' => hash('sha256', '1234')]);

    Livewire::test('pages::auth-code')
        ->set('code', '1234')
        ->call('joinSession')
        ->assertNoRedirect();

    expect(session('access_token_id'))->toBeNull();
});

test('non-digit or wrong-length codes fail validation', function () {
    Livewire::test('pages::auth-code')
        ->set('code', 'ab')
        ->call('joinSession')
        ->assertHasErrors(['code']);
});

test('repeated failed attempts are throttled per IP', function () {
    AccessToken::factory()->create(['token' => hash('sha256', '1234')]);

    RateLimiter::clear('access-token-login:127.0.0.1');

    for ($i = 0; $i < 8; $i++) {
        Livewire::test('pages::auth-code')
            ->set('code', '9999')
            ->call('joinSession');
    }

    Livewire::test('pages::auth-code')
        ->set('code', '1234')
        ->call('joinSession')
        ->assertNoRedirect();

    expect(session('access_token_id'))->toBeNull();
});

test('an already-authenticated visitor is redirected away from the login page', function () {
    $token = AccessToken::factory()->create();
    session(['access_token_id' => $token->id]);

    Livewire::test('pages::auth-code')
        ->assertRedirect(route('dashboard'));
});
