---
paths:
  - 'app/{Support/Grammar/**,Actions/GenerateInflectedForm.php,Actions/GetParadigm.php,Actions/ValidateGeneratedSentence.php,Ai/Agents/GreekSentenceWriter.php}'
---

# Agents

## Morphology drills: config-driven engine, Groq only offline-ish, accent-blind matching
Inflection paradigms live ONLY in config/grammar/{lang}.php — never hardcode endings in PHP. New paradigm = config entry + a row in the golden dataset (tests/Unit/Grammar/FormGeneratorTest.php); the agent must not invent paradigms.

GenerateInflectedForm just concatenates stem+ending and does NOT place the accent (ἄνθρωπος→ἀνθρωπου). GreekFormComparator is deliberately accent-blind (NFD, strip \p{Mn} incl. iota subscript, lowercase, fold final sigma). Everything grades through the comparator — never `===` on Greek strings.

AI (laravel/ai, GreekSentenceWriter) runs ONLY from the "Gerar frases" screen action (GenerateSentencesOrchestrator), never in a study/drill/correction path. Provider Groq, model `openai/gpt-oss-120b` (llama-3.3-* 404s on this account — hit GET /openai/v1/models before swapping). The model is never trusted: ValidateGeneratedSentence re-derives every inflected surface and rejects mismatches before persisting as `pending`. Distractors in the drill are always the same lexeme in another case/number.

Card morphology (`stem`,`paradigm_slug`,`gender`,`nom_sg_override`, all nullable) is filled on the dedicated /decks/{deck}/morphology screen — do NOT add these to CardForm/⚡edit-card-modal. Card::scopeDeclinable = has stem+paradigm_slug.
