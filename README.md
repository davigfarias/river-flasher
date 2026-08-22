# River Flasher

A flashcard app for studying biblical Greek and Hebrew vocabulary, built with Laravel and Livewire. Part of the "river" series of apps, sharing a single lightweight access-token auth system across the family of apps.

## Features

- **Decks & cards** — organize vocabulary into decks; a deck's language is inferred from its first card. Each card supports word, transliteration, part of speech, definition, example, and translation, with Greek and Hebrew (RTL-aware) rendering. Cards can also be flagged as difficult.
- **Recall-based study** — no spaced-repetition scheduling. Each card just tracks how many times you've remembered it vs. forgotten it; the study queue prioritizes cards you've missed more than you've gotten right, then never-studied cards, then the least-reviewed ones. Study a single deck or every deck at once ("estudar todos").
- **Deactivate cards** — take a card out of study (individually, or in bulk via select-all + confirmation) while you're still filling it in, without deleting it. Deactivated cards are hidden from the deck view until you choose to reveal them again.
- **Decks by tag** — build a new deck from an existing part-of-speech tag (e.g. every preposition, in one language): pick a language and a tag, toggle-select from the matching cards as they load, and they're moved into the new deck.
- **Dashboard** — study streak, an activity chart (7 days / 30 days / all time), today's recall percentage, how many cards need reinforcing, and recent decks.
- **Token-based access** — no registration or passwords. A 4-digit code, minted via Artisan, unlocks the app for a session. The `access_tokens` table is shared across every app in the river series against one production database.

## Tech Stack

- [Laravel](https://laravel.com) 13
- [Livewire](https://livewire.laravel.com) 4 with [Flux](https://fluxui.dev) UI components and [Blaze](https://github.com/livewire/blaze) for optimized rendering
- [Tailwind CSS](https://tailwindcss.com) 4 + [Vite](https://vitejs.dev)
- [Pest](https://pestphp.com) for testing, [Pint](https://laravel.com/docs/pint) for style, [Larastan](https://github.com/larastan/larastan) for static analysis
- SQLite by default (any Laravel-supported database works)

## Requirements

- PHP 8.3+
- Composer
- Node.js & npm

## Installation

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
npm run build
```

Or, once dependencies are installed, run the bundled setup script:

```bash
composer run setup
```

## Development

Start the app, queue worker, log watcher, and Vite dev server together:

```bash
composer run dev
```

## Access Tokens

There is no registration UI and no login/logout in the traditional sense — access is granted by a shared 4-digit token. Manage tokens via Artisan:

```bash
php artisan token:generate "Name/label for the token"
php artisan token:list
php artisan token:revoke {id} [--force]
```

Copy the generated code immediately — it is only shown once. Any river-series app accepts the same token.

## Testing

```bash
composer run test
```

This runs config clearing, Pint (style check), Larastan (static analysis), and the Pest test suite. To run just the tests:

```bash
php artisan test --compact
```

## Code Style & Static Analysis

```bash
composer run lint    # fix style issues with Pint
composer run types:check   # run Larastan
```

## Project Structure

Business logic is organized into small, single-responsibility Actions (`app/Actions/`), composed by Orchestrators (`app/Actions/Orchestrators/`) for anything spanning multiple steps. Livewire pages live under `resources/views/pages/`, each paired with a PHP class of the same name. See `.ai/rules/` for the project's documented conventions.

## License

Licensed under the [MIT license](https://opensource.org/licenses/MIT).
