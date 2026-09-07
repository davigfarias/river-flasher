---
paths:
  - 'database/**'
---

# Database

## NEVER wipe the local sqlite dev DB (database/database.sqlite)
database/database.sqlite is the developer's local data (login token + hand-test decks/cards). Gitignored, no backup. Wiping it locks the dev out of the app.

FORBIDDEN against the default connection / that file: migrate:fresh, migrate:rollback, migrate:reset, migrate:refresh, db:wipe, mass truncate/delete in tinker.

OK: `php artisan migrate` (forward only). Tests are already safe — phpunit.xml sets DB_DATABASE=:memory:, so `php artisan test` / pest never touch the dev DB.

To test a migration down()/fresh build: use a throwaway :memory: or a temp scratchpad sqlite file, never the real one. If a task seems to need a schema rebuild, STOP and ask.

If it gets wiped: recreate the token — AccessToken::updateOrCreate(['token'=>hash('sha256','<code>')],['name'=>'Dave']) — tell the dev, offer `db:seed --class=DemoSeeder`.
