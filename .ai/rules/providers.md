---
paths:
  - app/Providers/AppServiceProvider.php
---

# Providers

## App is pt-BR only — Carbon locale needs an explicit sync
The app is entirely Portuguese (pt-BR); there is no lang/ layer for UI strings — Blade views hardcode Portuguese text directly. Only `lang/pt_BR/validation.php` exists, for form error messages (with an `attributes` map translating field names like `word` -> `palavra`).

Setting `APP_LOCALE=pt_BR` alone does NOT localize Carbon's `diffForHumans()`/`translatedFormat()` — Laravel doesn't sync Carbon's locale with `app.locale` automatically. `AppServiceProvider::configureDefaults()` calls `Carbon::setLocale(config('app.locale'))` explicitly to fix this; don't remove it or dates revert to English.
