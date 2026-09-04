---
paths:
  - 'resources/views/pages/⚡study/*.php'
---

# Study

## Study card's answer/advance split prevents next-card spoiler during the flip-back animation
The flip card uses a CSS 3D transform (`rotate-x-180`, `duration-500`) toggled by `$revealed`. Advancing `$index` in the same Livewire request that resets `$revealed` used to spoil the next card: Livewire's DOM diff swaps in the next card's back-face content before the flip-back animation finishes turning it away from the viewer. Fix: `answer()` only grades the card and sets `$revealed = false` (flips back to the *current* card's own front — no spoiler); a separate `advance()` bumps `$index`/unsets the `card` computed. The Alpine `submitAnswer()` helper in study.blade.php calls `$wire.answer()` then, after a `setTimeout` matching the 500ms transition, calls `$wire.advance()` — and a `transitioning` flag disables the card/buttons/keyboard shortcuts in between so a fast click or keypress can't desync the two steps. Tests must call `->call('answer', ...)->call('advance')` (not just `answer`) to see `$index` move.

## Multi-deck custom sessions read `?decks=` from the raw query string, not a mount() param
`mount(StartStudySessionOrchestrator $orchestrator, ?string $deck = null)` still gets a single deck uuid from the `/study/{deck?}` route segment (Laravel injects route params into mount() by name). A custom multi-deck session instead arrives as `?decks=uuid1,uuid2` on plain `/study` — read via `request()->query('decks')` inside mount(), NOT as a second named mount parameter, because query-string values aren't auto-injected into mount() args the way route segments are. `$deckUuids` (locked array, empty = every deck) is stored so `restart()` rebuilds the exact same custom session instead of falling back to "study everything". See `.ai/rules/app-actions.md` for the FindCardsToStudy/StartStudySessionOrchestrator side of this.
