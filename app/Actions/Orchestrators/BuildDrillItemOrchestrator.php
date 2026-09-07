<?php

declare(strict_types=1);

namespace App\Actions\Orchestrators;

use App\Actions\GenerateInflectedForm;
use App\Actions\GetParadigm;
use App\Actions\PickDrillSentence;
use App\DTO\DrillItem;
use App\Enums\GrammaticalCase;
use App\Enums\GrammaticalNumber;
use App\Models\SentenceToken;
use App\Support\Grammar\GreekFormComparator;
use Illuminate\Support\Collection;
use Throwable;

final readonly class BuildDrillItemOrchestrator
{
    public function __construct(
        private PickDrillSentence $pickDrillSentence,
        private GetParadigm $getParadigm,
        private GenerateInflectedForm $generateInflectedForm,
        private GreekFormComparator $comparator,
    ) {}

    public function handle(int $deckId, ?string $grammarFocus = null): ?DrillItem
    {
        $sentence = $this->pickDrillSentence->handle($deckId, $grammarFocus);

        if ($sentence === null) {
            return null;
        }

        /** @var Collection<int, SentenceToken> $targets */
        $targets = $sentence->tokens->filter(
            fn (SentenceToken $token): bool => $token->is_target
                && $token->card !== null
                && $token->card->stem !== null
                && $token->card->paradigm_slug !== null
                && $token->grammatical_case !== null
                && $token->grammatical_number !== null,
        );

        $target = $targets->random();

        $ordered = $sentence->tokens->sortBy('position')->values();
        $blankIndex = $ordered->search(fn (SentenceToken $token): bool => $token->id === $target->id);

        return new DrillItem(
            sentenceId: $sentence->id,
            tokenId: $target->id,
            before: $ordered->take($blankIndex)->pluck('surface')->all(),
            after: $ordered->slice($blankIndex + 1)->pluck('surface')->all(),
            options: $this->wordBank($target),
            correctSurface: $target->surface,
            analysis: $target->grammatical_case->label().' '.$target->grammatical_number->label(),
            lemma: $target->card->word,
            translation: $sentence->translation_pt,
        );
    }

    /**
     * The correct form plus up to three distractors: the same lexeme in
     * other cases/numbers. Never another word — the trap is grammatical.
     *
     * @return array<int, string>
     */
    private function wordBank(SentenceToken $target): array
    {
        $options = [$target->surface];

        try {
            $paradigm = $this->getParadigm->handle($target->card->paradigm_slug);
        } catch (Throwable) {
            return $options;
        }

        $combos = [];

        foreach (GrammaticalCase::cases() as $case) {
            foreach (GrammaticalNumber::cases() as $number) {
                $combos[] = [$case, $number];
            }
        }

        shuffle($combos);

        foreach ($combos as [$case, $number]) {
            if (count($options) >= 4) {
                break;
            }

            $form = $this->generateInflectedForm->handle(
                $target->card->stem,
                $paradigm,
                $case,
                $number,
                $target->card->nom_sg_override,
            );

            if ($form === '') {
                continue;
            }

            foreach ($options as $existing) {
                if ($this->comparator->matches($existing, $form)) {
                    continue 2;
                }
            }

            $options[] = $form;
        }

        shuffle($options);

        return $options;
    }
}
