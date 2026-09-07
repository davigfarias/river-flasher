<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\GrammaticalCase;
use App\Enums\GrammaticalNumber;
use App\Models\Card;
use App\Support\Grammar\GreekFormComparator;
use Illuminate\Support\Collection;
use Throwable;

/**
 * Deterministically checks one AI-generated sentence. Returns a rejection
 * reason (Portuguese, shown in the run report) or null if the sentence is
 * good enough to store as `pending`. The AI never corrects — this does.
 */
final readonly class ValidateGeneratedSentence
{
    public function __construct(
        private GetParadigm $getParadigm,
        private GenerateInflectedForm $generateInflectedForm,
        private GreekFormComparator $comparator,
    ) {}

    /**
     * @param  array{text?: string, tokens?: array<int, array{surface?: string, lemma?: string, case?: ?string, number?: ?string}>}  $sentence
     * @param  Collection<int, Card>  $deckCards
     */
    public function handle(array $sentence, Collection $deckCards, GrammaticalCase $focus): ?string
    {
        $text = trim((string) ($sentence['text'] ?? ''));
        $tokens = $sentence['tokens'] ?? [];

        if ($text === '' || $tokens === []) {
            return 'frase ou tokens vazios';
        }

        $surfaces = array_map(fn (array $token): string => trim((string) ($token['surface'] ?? '')), $tokens);

        if (in_array('', $surfaces, true)) {
            return 'token sem surface';
        }

        if (preg_replace('/\s+/', ' ', $text) !== implode(' ', $surfaces)) {
            return "text não é a junção dos surfaces: \"{$text}\"";
        }

        $cardsByLemma = $deckCards->keyBy(fn (Card $card): string => $this->comparator->normalize($card->word));

        $usesDeckVocab = false;
        $hasFocusCase = false;

        foreach ($tokens as $token) {
            $case = $this->enumCase($token['case'] ?? null);
            $number = $this->enumNumber($token['number'] ?? null);
            $card = $cardsByLemma->get($this->comparator->normalize((string) ($token['lemma'] ?? '')));

            if ($card !== null) {
                $usesDeckVocab = true;
            }

            if ($case === $focus) {
                $hasFocusCase = true;
            }

            if ($card === null || $case === null || $number === null) {
                continue;
            }

            try {
                $paradigm = $this->getParadigm->handle($card->paradigm_slug);
            } catch (Throwable) {
                continue;
            }

            $expected = $this->generateInflectedForm->handle(
                $card->stem,
                $paradigm,
                $case,
                $number,
                $card->nom_sg_override,
            );

            if (! $this->comparator->matches($expected, (string) $token['surface'])) {
                return "morfologia errada: \"{$token['surface']}\" deveria ser \"{$expected}\" ({$card->word}, {$case->label()} {$number->label()})";
            }
        }

        if (! $usesDeckVocab) {
            return 'nenhuma palavra do baralho foi usada';
        }

        if (! $hasFocusCase) {
            return "nenhum token no caso pedido ({$focus->label()})";
        }

        return null;
    }

    private function enumCase(?string $value): ?GrammaticalCase
    {
        return $value === null ? null : GrammaticalCase::tryFrom($value);
    }

    private function enumNumber(?string $value): ?GrammaticalNumber
    {
        return $value === null ? null : GrammaticalNumber::tryFrom($value);
    }
}
