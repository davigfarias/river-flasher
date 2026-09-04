---
paths:
  - 'resources/views/pages/⚡study/*.php'
---

# Study

## Study card's answer/advance split prevents next-card spoiler during the flip-back animation
The flip card uses a CSS 3D transform (`rotate-x-180`, `duration-500`) toggled by `$revealed`. Advancing `$index` in the same Livewire request that resets `$revealed` used to spoil the next card: Livewire's DOM diff swaps in the next card's back-face content before the flip-back animation finishes turning it away from the viewer. Fix: `answer()` only grades the card and sets `$revealed = false` (flips back to the *current* card's own front — no spoiler); a separate `advance()` bumps `$index`/unsets the `card` computed. The Alpine `submitAnswer()` helper in study.blade.php calls `$wire.answer()` then, after a `setTimeout` matching the 500ms transition, calls `$wire.advance()` — and a `transitioning` flag disables the card/buttons/keyboard shortcuts in between so a fast click or keypress can't desync the two steps. Tests must call `->call('answer', ...)->call('advance')` (not just `answer`) to see `$index` move.

## Multi-deck custom sessions read `?decks=` from the raw query string, not a mount() param
`mount(StartStudySessionOrchestrator $orchestrator, ?string $deck = null)` still gets a single deck uuid from the `/study/{deck?}` route segment (Laravel injects route params into mount() by name). A custom multi-deck session instead arrives as `?decks=uuid1,uuid2` on plain `/study` — read via `request()->query('decks')` inside mount(), NOT as a second named mount parameter, because query-string values aren't auto-injected into mount() args the way route segments are. `$deckUuids` (locked array, empty = every deck) is stored so `restart()` rebuilds the exact same custom session instead of falling back to "study everything". See `.ai/rules/app-actions.md` for the FindCardsToStudy/StartStudySessionOrchestrator side of this.

## goBack() undo stack for correcting mistaken study answers
`answer()` pushes one entry onto public array `$history` per grade given: `{index, cardId, reviewId, previousAcedCount, previousMissedCount, previousLastReviewedAt, requeued}`. `goBack()` pops the top entry (LIFO — always undoes the most recent answer only), calls `UndoAnswerOrchestrator` (deletes that Review row, restores the card's counters/last_reviewed_at), then reverses the session-state side effect: if `requeued` (a "não lembrei"), `array_pop($cardIds)` removes the duplicate `answer()` appended to the tail; otherwise decrement `completedCount`. `$index` is set back to the entry's `index` and `revealed = true` so the user sees the previous card's answer immediately and can re-grade it.

Traps:
- `previousLastReviewedAt` is stored as an ISO string, not a `CarbonImmutable` — Livewire can't hydrate arbitrary objects nested inside a plain array property across requests. Parse it back with `CarbonImmutable::parse()` only when calling the orchestrator.
- `reviewId` is looked up via `Review::where('card_id', ...)->latest('id')->value('id')` right after `AnswerCardOrchestrator::handle()` returns, rather than changing that orchestrator's return type — its return value is asserted directly in `AnswerCardOrchestratorTest`.
- The requeued-tail-pop is only safe because `history` entries are pushed 1:1 with `cardIds` pushes and popped in the same LIFO order — never reorder or filter `$history` independently of `$cardIds`.
- `$history` is reset to `[]` in `startSession()` (mount and restart), same as `completedCount`/`index`.
