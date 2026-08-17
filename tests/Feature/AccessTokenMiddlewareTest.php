<?php

use App\Models\AccessToken;
use Illuminate\Support\Facades\Log;
use Livewire\Livewire;

test('guests are redirected to the login page for every protected route', function (string $uri) {
    $this->get($uri)->assertRedirect(route('home'));
})->with([
    '/dashboard',
    '/study',
    '/flashcards/create',
]);

test('a plain guest visit does not flag anything — nothing was ever signed in', function () {
    Log::shouldReceive('warning')->never();

    $this->get('/dashboard')
        ->assertRedirect(route('home'))
        ->assertSessionMissing('access_token_invalidated');
});

test('a valid session can reach a protected route', function () {
    $token = AccessToken::factory()->create();

    $this->withSession(['access_token_id' => $token->id])
        ->get('/dashboard')
        ->assertOk();
});

test('revoking the token kills an already-active session on the next request', function () {
    $token = AccessToken::factory()->create();

    $this->withSession(['access_token_id' => $token->id])
        ->get('/dashboard')
        ->assertOk();

    $token->update(['revoked_at' => now()]);

    $this->withSession(['access_token_id' => $token->id])
        ->get('/dashboard')
        ->assertRedirect(route('home'))
        ->assertSessionHas('access_token_invalidated', true);
});

test('an unknown token id in the session is rejected and leaves a log trail', function () {
    Log::shouldReceive('warning')
        ->once()
        ->with('Access token session invalidated', Mockery::on(
            fn (array $context) => $context['access_token_id'] === 999999
        ));

    $this->withSession(['access_token_id' => 999999])
        ->get('/dashboard')
        ->assertRedirect(route('home'))
        ->assertSessionHas('access_token_invalidated', true);
});

test('the login page toasts when it finds an invalidated-token flash', function () {
    session()->flash('access_token_invalidated', true);

    Livewire::test('pages::auth-code')
        ->assertDispatched('toast-show');
});

test('a plain first visit to the login page shows no toast', function () {
    Livewire::test('pages::auth-code')
        ->assertNotDispatched('toast-show');
});
