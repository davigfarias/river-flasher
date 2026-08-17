<?php

use App\Actions\ValidateAccessToken;
use Flux\Flux;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\{Layout, Title};
use Livewire\Component;

new #[Layout('layouts::guest')] #[Title('Enter Access Code')] class extends Component
{
    public string $code = '';

    public function mount(): void
    {
        if (session('access_token_id')) {
            $this->redirect(route('dashboard'), navigate: true);

            return;
        }

        if (session()->pull('access_token_invalidated')) {
            Flux::toast(
                text: 'Your session ended because your access code is no longer valid. Enter it again, or a new one if it was regenerated.',
                variant: 'warning',
            );
        }
    }

    public function joinSession(ValidateAccessToken $action): void
    {
        $this->validate([
            'code' => ['required', 'digits:4'],
        ]);

        $key = 'access-token-login:'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 8)) {
            $this->code = '';
            Flux::toast(text: 'Too many attempts. Please wait a few minutes.', variant: 'danger');

            return;
        }

        RateLimiter::hit($key, 300);

        $outcome = $action->handle($this->code);

        if (! $outcome->success) {
            $this->code = '';
            Flux::toast(text: $outcome->message, variant: 'danger');

            return;
        }

        RateLimiter::clear($key);

        session()->regenerate();
        session(['access_token_id' => $outcome->data->id]);

        $this->redirect(route('dashboard'), navigate: true);
    }
};
