<?php

declare(strict_types=1);

namespace App\Actions\Orchestrators;

use App\Actions\FindDeckCardsForSentences;
use App\Actions\PersistSentence;
use App\Actions\ValidateGeneratedSentence;
use App\Ai\Agents\GreekSentenceWriter;
use App\DTO\SentenceData;
use App\DTO\SentenceGenerationSummary;
use App\DTO\SentenceTokenData;
use App\Enums\GrammaticalCase;
use App\Enums\GrammaticalNumber;
use App\Enums\SentenceSource;
use App\Models\Card;
use App\Models\Deck;
use App\Support\Grammar\GreekFormComparator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The "Gerar frases" screen action. Prompts the Groq agent a few times,
 * runs every sentence through the deterministic validator, and stores the
 * survivors as `pending`. The model is never trusted — the validator is.
 */
final readonly class GenerateSentencesOrchestrator
{
    private const int SENTENCES_PER_CALL = 6;

    public function __construct(
        private FindDeckCardsForSentences $findDeckCardsForSentences,
        private ValidateGeneratedSentence $validateGeneratedSentence,
        private PersistSentence $persistSentence,
        private GreekFormComparator $comparator,
    ) {}

    public function handle(int $accessTokenId, Deck $deck, GrammaticalCase $case, int $calls = 3): SentenceGenerationSummary
    {
        $cards = $this->findDeckCardsForSentences->handle($deck);

        if ($cards->isEmpty()) {
            return SentenceGenerationSummary::blocked(
                'Nenhum cartão deste baralho tem radical e paradigma. Anote a morfologia primeiro.',
            );
        }

        $prompt = $this->buildPrompt($cards, $case);

        $rawSentences = [];
        $seen = [];

        for ($i = 0; $i < max(1, $calls); $i++) {
            $response = (new GreekSentenceWriter)->prompt($prompt);

            foreach ($response['sentences'] ?? [] as $sentence) {
                $key = $this->comparator->normalize((string) ($sentence['text'] ?? ''));

                if ($key === '' || isset($seen[$key])) {
                    continue;
                }

                $seen[$key] = true;
                $rawSentences[] = $sentence;
            }
        }

        $accepted = 0;
        $reasons = [];

        DB::transaction(function () use ($rawSentences, $cards, $case, $deck, $accessTokenId, &$accepted, &$reasons): void {
            foreach ($rawSentences as $sentence) {
                $reason = $this->validateGeneratedSentence->handle($sentence, $cards, $case);

                if ($reason !== null) {
                    $reasons[] = $reason;

                    continue;
                }

                $this->persistSentence->handle(
                    $deck,
                    $accessTokenId,
                    SentenceSource::Ai,
                    $this->toSentenceData($sentence, $case),
                );

                $accepted++;
            }
        });

        return new SentenceGenerationSummary(
            generated: count($rawSentences),
            accepted: $accepted,
            rejected: count($reasons),
            rejectionReasons: $reasons,
        );
    }

    /**
     * @param  Collection<int, Card>  $cards
     */
    private function buildPrompt($cards, GrammaticalCase $case): string
    {
        $count = self::SENTENCES_PER_CALL;

        $vocab = $cards
            ->map(fn (Card $card): string => "- {$card->word} — {$card->definition}")
            ->implode("\n");

        return <<<PROMPT
        Escreva {$count} frases curtas em grego koiné.

        Vocabulário permitido (use SÓ estes substantivos como palavras de conteúdo):
        {$vocab}

        Caso gramatical alvo: {$case->value} ({$case->label()}). Pelo menos um substantivo
        de cada frase deve estar nesse caso.
        PROMPT;
    }

    /**
     * @param  array{text?: string, translation_pt?: string, tokens?: array<int, array<string, mixed>>}  $sentence
     */
    private function toSentenceData(array $sentence, GrammaticalCase $case): SentenceData
    {
        $tokens = [];

        foreach (array_values($sentence['tokens'] ?? []) as $position => $token) {
            $tokens[] = new SentenceTokenData(
                position: $position,
                surface: (string) $token['surface'],
                lemma: isset($token['lemma']) ? (string) $token['lemma'] : null,
                case: isset($token['case']) ? GrammaticalCase::tryFrom((string) $token['case']) : null,
                number: isset($token['number']) ? GrammaticalNumber::tryFrom((string) $token['number']) : null,
            );
        }

        return new SentenceData(
            text: trim((string) $sentence['text']),
            translationPt: trim((string) ($sentence['translation_pt'] ?? '')),
            grammarFocus: $case->value,
            tokens: $tokens,
        );
    }
}
