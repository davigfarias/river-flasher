---
paths:
  - 'resources/views/pages/⚡deck-morphology/*.php'
---

# Deck Morphology

## Morphology screen: cards.morphology_excluded_at hides indeclinable words
The deck-morphology screen has a trash icon per card. It calls SetCardMorphologyExclusion, which stamps cards.morphology_excluded_at (nullable timestamp). Excluded = user judged the word indeclinable (adverb, particle…); it drops off the annotation list. Reversible via the "Removidas (N)" checkbox -> showExcluded view -> Restaure. greekCards() still returns everything; cards()/annotatedCount()/excludedCount() do the filtering. save() early-returns while showExcluded so it can't null out morphology on excluded rows. Future: auto-derive this from card POS so only declinable words show.
