---
paths:
  - 'app/Actions/**'
  - 'app/Actions/{PersistTranslationExercise,BuildTranslationWordBank,TokenizeText,CompareTranslationTokens,ResetTranslationBank}.php'
---

# Actions

## Actions split into loose mini actions + Orchestrators/ subfolder
Mini actions (one handle(), one responsibility, at most one query/write, never call other minis) live directly in app/Actions/ with no per-domain subfolders. Orchestrators (compose 2+ minis, may open DB::transaction) live in app/Actions/Orchestrators/ with an "Orchestrator" suffix. If a change needs more than one thing to happen, it becomes (or belongs in) an Orchestrator — don't grow a mini into doing two things.

## Translation exercises: cached parse, fresh distractors, no AI
The tradução study mode reuses each card's own `example` (target sentence) + `translation` (PT) — never the AI `sentences`/`sentence_tokens` tables (those are Greek-only morphology drills). `translation_exercises` (one row per card, `card_id` unique) caches only the parsed correct token sequence + target_text; it's built lazily by PrepareTranslationCardOrchestrator the first time a card comes up. Distractors are NOT stored — BuildTranslationWordBank draws them fresh each session from other deck cards' `translation` words + this card's `definition`, filtering stopwords/short tokens/answer words, ranked by shared category/pos. "Resetar banco de frases" on deck-show (ResetTranslationBank) deletes the deck's rows so edits to a card's translation take effect. CompareTranslationTokens grades: case/punctuation-insensitive, order- and accent-sensitive.
