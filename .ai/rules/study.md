---
paths:
  - 'resources/views/pages/⚡study/*.php'
---

# Study

## Study card's answer/advance split prevents next-card spoiler during the flip-back animation
The flip card uses a CSS 3D transform (`rotate-x-180`, `duration-500`) toggled by `$revealed`. Advancing `$index` in the same Livewire request that resets `$revealed` used to spoil the next card: Livewire's DOM diff swaps in the next card's back-face content before the flip-back animation finishes turning it away from the viewer. Fix: `answer()` only grades the card and sets `$revealed = false` (flips back to the *current* card's own front — no spoiler); a separate `advance()` bumps `$index`/unsets the `card` computed. The Alpine `submitAnswer()` helper in study.blade.php calls `$wire.answer()` then, after a `setTimeout` matching the 500ms transition, calls `$wire.advance()` — and a `transitioning` flag disables the card/buttons/keyboard shortcuts in between so a fast click or keypress can't desync the two steps. Tests must call `->call('answer', ...)->call('advance')` (not just `answer`) to see `$index` move.
