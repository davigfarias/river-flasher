---
paths:
  - 'app/{Models/AccessToken.php,Http/Middleware/EnsureAccessTokenIsValid.php,Console/Commands/*AccessToken*.php}'
---

# Commands

## Auth is a shared 4-digit access_tokens system, not Laravel Auth
This app has no Auth::login/guards/users-table usage. Login is: 4-digit code -> SHA-256 lookup in `access_tokens` -> session('access_token_id') -> EnsureAccessTokenIsValid middleware (registered both on protected routes and via Livewire::addPersistentMiddleware in AppServiceProvider, or Livewire updates bypass the check). The `access_tokens` table is intentionally shared across every app in the "river" series against one production MySQL database on Laravel Cloud — its migration is guarded with `Schema::hasTable()` and has an empty down() so it's safe for multiple apps to migrate against the same DB. One token unlocks all river apps. Mint/revoke tokens only via `php artisan token:generate|list|revoke` (see app/Console/Commands) — there is no registration UI and no logout by design (mirrors river-notetaker's pattern).
