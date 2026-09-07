<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\GenerateInflectedForm;
use App\Actions\GetParadigm;
use App\Enums\GrammaticalCase;
use App\Enums\GrammaticalNumber;
use App\Models\Card;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('grammar:paradigm {lexeme : The card word, or any label for the output} {--language=greek} {--slug= : Paradigm slug; falls back to a matching card} {--stem= : Stem; falls back to a matching card}')]
#[Description('Prints the full declension of a lexeme so it can be checked against the reference grammar')]
class ShowParadigmCommand extends Command
{
    public function __construct(
        private readonly GetParadigm $getParadigm,
        private readonly GenerateInflectedForm $generateInflectedForm,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $lexeme = (string) $this->argument('lexeme');
        $language = (string) $this->option('language');

        $slug = $this->option('slug');
        $stem = $this->option('stem');
        $nomSgOverride = null;

        if ($slug === null || $stem === null) {
            $card = Card::query()->where('word', $lexeme)->first();

            if ($card === null) {
                $this->error("Pass --slug and --stem, or seed a card with word [{$lexeme}].");

                return self::FAILURE;
            }

            $slug ??= $card->paradigm_slug;
            $stem ??= $card->stem;
            $nomSgOverride = $card->nom_sg_override;
        }

        if ($slug === null || $stem === null) {
            $this->error("Card [{$lexeme}] has no paradigm_slug/stem set.");

            return self::FAILURE;
        }

        try {
            $paradigm = $this->getParadigm->handle($slug, $language);
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info("{$lexeme} — {$paradigm->label} (stem: {$stem})");

        $rows = array_map(fn (GrammaticalCase $case): array => [
            $case->label(),
            $this->generateInflectedForm->handle($stem, $paradigm, $case, GrammaticalNumber::Singular, $nomSgOverride) ?: '—',
            $this->generateInflectedForm->handle($stem, $paradigm, $case, GrammaticalNumber::Plural, $nomSgOverride) ?: '—',
        ], GrammaticalCase::cases());

        $this->table(['Caso', 'Singular', 'Plural'], $rows);

        return self::SUCCESS;
    }
}
