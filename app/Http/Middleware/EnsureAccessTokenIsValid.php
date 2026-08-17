<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Actions\CheckAccessTokenStillValid;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

final readonly class EnsureAccessTokenIsValid
{
    public function __construct(private CheckAccessTokenStillValid $action) {}

    public function handle(Request $request, Closure $next): Response
    {
        $accessTokenId = $request->session()->get('access_token_id');

        if (! $accessTokenId) {
            return redirect()->route('home');
        }

        if ($this->action->handle((int) $accessTokenId)->success) {
            return $next($request);
        }

        // A token id that no longer resolves means someone *was* signed
        // in and silently lost access (revoked token, wiped database,
        // bad id) — worth a trail, unlike the ordinary logged-out state
        // above where nothing was ever signed in.
        Log::warning('Access token session invalidated', [
            'access_token_id' => $accessTokenId,
            'ip' => $request->ip(),
            'path' => $request->path(),
        ]);

        $request->session()->flash('access_token_invalidated', true);
        $request->session()->forget('access_token_id');

        return redirect()->route('home');
    }
}
