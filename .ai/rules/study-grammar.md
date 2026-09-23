---
paths:
  - 'resources/views/pages/⚡study-grammar/*.php'
---

# Study Grammar

## Tradução flow now goes through a declension picker + theory page first
Tradução exercises read the card's own `example`/`translation` (see .ai/rules/actions.md) — never the AI sentences tables — so the only grammar metadata to filter by is `paradigm_slug`, not GrammaticalCase. The "Tradução" button in study-mode-modal now links to `study.grammar` (`⚡study-grammar`) instead of straight to `/study`: step 1 lists declensions via GetGrammarStudyOptions (only ones with >=1 eligible card — no minimum like GetDeckStudyModes' 5, since filtering by declension naturally shrinks the pool further), step 2 shows that paradigm's case×number endings table (reusing Paradigm::endingFor, zero new content), "Começar" redirects into `/study?mode=translation&paradigm={slug}`. `FindCardsToStudy`/`StartStudySessionOrchestrator` both take an optional `paradigmSlug`, applied only when mode is Translation. `study.php` keeps it as `#[Url] public ?string $paradigm`, same pattern as `$starred`, so `restart()` stays scoped. Hebrew-ready by construction (language param already exists on GetParadigm/ListParadigms) but not wired here — config/grammar/hebrew.php is still an empty Fase 2 stub, so the picker will just show zero options for Hebrew cards, same as the morphology screen.
