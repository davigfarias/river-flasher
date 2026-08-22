---
paths:
  - 'app/{Actions/CalculateRecallCounters.php,Models/Card.php,DTO/RecallCounters.php}'
  - 'app/{Actions/StoreCardImage.php,Actions/DeleteCardImage.php,Models/Card.php}'
---

# App Actions

## Recall counters replaced SM-2 — CalculateRecallCounters and Card::scopeToReinforce are the only sources of truth
There is no spaced-repetition scheduling anymore. Each card just tracks `aced_count` (lembrei) and `missed_count` (não lembrei). `CalculateRecallCounters` is the single pure action that increments the right one for a `ReviewResult` — no DB, no clock reads — used by `AnswerCardOrchestrator` when persisting a real answer. "Needs reinforcing" is defined exactly once, as `Card::scopeToReinforce` (`missed_count > aced_count`, not `missed_count > 0`, so a card corrects itself back off the list as it's answered right) — every action that needs this list (`CountCardsToReinforce`, `GetRecentDecksSummary`, `FindCardsToStudy`'s ordering) goes through the scope rather than re-deriving the comparison.

## Card images use the default filesystem disk, no per-object visibility
`Card::image_path` is stored on whatever disk `config('filesystems.default')` resolves to (env `FILESYSTEM_DISK`, `public` locally). `StoreCardImage` always calls plain `store()`, never `storePublicly()`/`storePubliclyAs()` — Laravel Cloud Object Storage buckets are Cloudflare R2, which sets visibility at the bucket level and rejects per-object ACL headers with a `NotImplemented` error. In production, mark the attached bucket "public" in Laravel Cloud instead. `Card::imageUrl()` wraps `Storage::url($this->image_path)` on the default disk — never hardcode a disk name when reading/writing card images.
