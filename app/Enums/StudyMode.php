<?php

namespace App\Enums;

/**
 * The three ways a card can be drilled in a study session. Each mode grades
 * onto its own pair of recall counters on the card so progress in one mode
 * never inflates or masks another.
 */
enum StudyMode: string
{
    case Meaning = 'meaning';
    case Reading = 'reading';
    case Translation = 'translation';

    public function label(): string
    {
        return match ($this) {
            self::Meaning => 'Significado',
            self::Reading => 'Leitura',
            self::Translation => 'Tradução',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Meaning => 'Veja a palavra, lembre o significado.',
            self::Reading => 'Leia a palavra em voz alta, confira a pronúncia.',
            self::Translation => 'Monte a tradução da frase com o banco de palavras.',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Meaning => 'light-bulb',
            self::Reading => 'speaker-wave',
            self::Translation => 'language',
        };
    }

    /**
     * The card column holding this mode's "acertou" tally.
     */
    public function acedColumn(): string
    {
        return match ($this) {
            self::Meaning => 'aced_count',
            self::Reading => 'reading_aced_count',
            self::Translation => 'translation_aced_count',
        };
    }

    /**
     * The card column holding this mode's "errou" tally.
     */
    public function missedColumn(): string
    {
        return match ($this) {
            self::Meaning => 'missed_count',
            self::Reading => 'reading_missed_count',
            self::Translation => 'translation_missed_count',
        };
    }

    /**
     * A raw "order by least-seen first" clause over this mode's counter pair,
     * used to bias a session toward cards the user has practised least in it.
     *
     * @return literal-string
     */
    public function leastSeenOrderClause(): string
    {
        return match ($this) {
            self::Meaning => 'aced_count + missed_count asc',
            self::Reading => 'reading_aced_count + reading_missed_count asc',
            self::Translation => 'translation_aced_count + translation_missed_count asc',
        };
    }
}
