---
paths:
  - 'app/{Actions/CalculateNextReview.php,Models/Card.php,DTO/ReviewOutcome.php}'
---

# App Actions

## SM-2 spaced-repetition constants live only in CalculateNextReview
CalculateNextReview is the single source of truth for the SRS math (ease floor 1.3, learning steps 1m/10m, hard-learning 6m, easy-graduation 4d, hard x1.2, easy bonus x1.3, 365d cap). It is pure — no DB, no clock reads beyond the $now argument passed in — so it doubles as both the real rating persister (via RateCardOrchestrator) and the button-label previewer (via PreviewNextIntervalsOrchestrator) with identical math. `Card::LEARNING_THRESHOLD_MINUTES` (1440) and `Card::MATURE_THRESHOLD_MINUTES` (30240) are the two thresholds other code should reference — don't duplicate these numbers elsewhere.
