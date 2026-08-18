<?php

use App\Actions\ValidateAccessToken;
use Flux\Flux;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\{Layout, Title};
use Livewire\Component;

new #[Layout('layouts::guest')] #[Title('Digite o código de acesso')] class extends Component
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
                text: 'Sua sessão terminou porque o código de acesso não é mais válido. Digite-o novamente, ou um novo código caso ele tenha sido regenerado.',
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
            Flux::toast(text: 'Muitas tentativas. Aguarde alguns minutos.', variant: 'danger');

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
