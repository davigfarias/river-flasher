---
paths:
  - 'app/{Actions/CalculateRecallCounters.php,Models/Card.php,DTO/RecallCounters.php}'
---

# App Actions

## Recall counters replaced SM-2 — CalculateRecallCounters and Card::scopeToReinforce are the only sources of truth
There is no spaced-repetition scheduling anymore. Each card just tracks `aced_count` (lembrei) and `missed_count` (não lembrei). `CalculateRecallCounters` is the single pure action that increments the right one for a `ReviewResult` — no DB, no clock reads — used by `AnswerCardOrchestrator` when persisting a real answer. "Needs reinforcing" is defined exactly once, as `Card::scopeToReinforce` (`missed_count > aced_count`, not `missed_count > 0`, so a card corrects itself back off the list as it's answered right) — every action that needs this list (`CountCardsToReinforce`, `GetRecentDecksSummary`, `FindCardsToStudy`'s ordering) goes through the scope rather than re-deriving the comparison.
