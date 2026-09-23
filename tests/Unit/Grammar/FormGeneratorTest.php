<?php

use App\Actions\GenerateInflectedForm;
use App\Actions\GenerateInflectedVerbForm;
use App\Enums\GrammaticalCase;
use App\Enums\GrammaticalNumber;
use App\Enums\GrammaticalPerson;
use App\Support\Grammar\GreekFormComparator;
use App\Support\Grammar\Paradigm;

/**
 * Loads the real config file without booting the framework, so this stays
 * a pure unit test of the engine + the shipped paradigm tables.
 */
function greekParadigm(string $slug): Paradigm
{
    /** @var array{paradigms: array<string, array<string, mixed>>} $greek */
    $greek = require __DIR__.'/../../../config/grammar/greek.php';

    return Paradigm::fromConfig($slug, $greek['paradigms'][$slug]);
}

/**
 * The golden contract of the engine: every form it builds for these three
 * nouns must match the paradigm in the reference grammar, judged by the
 * accent-blind comparator the drills use. Add a paradigm to
 * config/grammar/greek.php → add its ten rows here.
 */
dataset('greek noun paradigms', [
    // λόγος — 2nd declension masculine, stem λογ
    'λόγος nom sg' => ['noun-2-masc', 'λογ', GrammaticalCase::Nominative, GrammaticalNumber::Singular, 'λόγος'],
    'λόγος nom pl' => ['noun-2-masc', 'λογ', GrammaticalCase::Nominative, GrammaticalNumber::Plural, 'λόγοι'],
    'λόγος gen sg' => ['noun-2-masc', 'λογ', GrammaticalCase::Genitive, GrammaticalNumber::Singular, 'λόγου'],
    'λόγος gen pl' => ['noun-2-masc', 'λογ', GrammaticalCase::Genitive, GrammaticalNumber::Plural, 'λόγων'],
    'λόγος dat sg' => ['noun-2-masc', 'λογ', GrammaticalCase::Dative, GrammaticalNumber::Singular, 'λόγῳ'],
    'λόγος dat pl' => ['noun-2-masc', 'λογ', GrammaticalCase::Dative, GrammaticalNumber::Plural, 'λόγοις'],
    'λόγος acc sg' => ['noun-2-masc', 'λογ', GrammaticalCase::Accusative, GrammaticalNumber::Singular, 'λόγον'],
    'λόγος acc pl' => ['noun-2-masc', 'λογ', GrammaticalCase::Accusative, GrammaticalNumber::Plural, 'λόγους'],
    'λόγος voc sg' => ['noun-2-masc', 'λογ', GrammaticalCase::Vocative, GrammaticalNumber::Singular, 'λόγε'],
    'λόγος voc pl' => ['noun-2-masc', 'λογ', GrammaticalCase::Vocative, GrammaticalNumber::Plural, 'λόγοι'],

    // γραφή — 1st declension feminine in -η, stem γραφ
    'γραφή nom sg' => ['noun-1-fem-eta', 'γραφ', GrammaticalCase::Nominative, GrammaticalNumber::Singular, 'γραφή'],
    'γραφή nom pl' => ['noun-1-fem-eta', 'γραφ', GrammaticalCase::Nominative, GrammaticalNumber::Plural, 'γραφαί'],
    'γραφή gen sg' => ['noun-1-fem-eta', 'γραφ', GrammaticalCase::Genitive, GrammaticalNumber::Singular, 'γραφῆς'],
    'γραφή gen pl' => ['noun-1-fem-eta', 'γραφ', GrammaticalCase::Genitive, GrammaticalNumber::Plural, 'γραφῶν'],
    'γραφή dat sg' => ['noun-1-fem-eta', 'γραφ', GrammaticalCase::Dative, GrammaticalNumber::Singular, 'γραφῇ'],
    'γραφή dat pl' => ['noun-1-fem-eta', 'γραφ', GrammaticalCase::Dative, GrammaticalNumber::Plural, 'γραφαῖς'],
    'γραφή acc sg' => ['noun-1-fem-eta', 'γραφ', GrammaticalCase::Accusative, GrammaticalNumber::Singular, 'γραφήν'],
    'γραφή acc pl' => ['noun-1-fem-eta', 'γραφ', GrammaticalCase::Accusative, GrammaticalNumber::Plural, 'γραφάς'],
    'γραφή voc sg' => ['noun-1-fem-eta', 'γραφ', GrammaticalCase::Vocative, GrammaticalNumber::Singular, 'γραφή'],
    'γραφή voc pl' => ['noun-1-fem-eta', 'γραφ', GrammaticalCase::Vocative, GrammaticalNumber::Plural, 'γραφαί'],

    // ἔργον — 2nd declension neuter, stem ἐργ
    'ἔργον nom sg' => ['noun-2-neut', 'ἐργ', GrammaticalCase::Nominative, GrammaticalNumber::Singular, 'ἔργον'],
    'ἔργον nom pl' => ['noun-2-neut', 'ἐργ', GrammaticalCase::Nominative, GrammaticalNumber::Plural, 'ἔργα'],
    'ἔργον gen sg' => ['noun-2-neut', 'ἐργ', GrammaticalCase::Genitive, GrammaticalNumber::Singular, 'ἔργου'],
    'ἔργον gen pl' => ['noun-2-neut', 'ἐργ', GrammaticalCase::Genitive, GrammaticalNumber::Plural, 'ἔργων'],
    'ἔργον dat sg' => ['noun-2-neut', 'ἐργ', GrammaticalCase::Dative, GrammaticalNumber::Singular, 'ἔργῳ'],
    'ἔργον dat pl' => ['noun-2-neut', 'ἐργ', GrammaticalCase::Dative, GrammaticalNumber::Plural, 'ἔργοις'],
    'ἔργον acc sg' => ['noun-2-neut', 'ἐργ', GrammaticalCase::Accusative, GrammaticalNumber::Singular, 'ἔργον'],
    'ἔργον acc pl' => ['noun-2-neut', 'ἐργ', GrammaticalCase::Accusative, GrammaticalNumber::Plural, 'ἔργα'],
    'ἔργον voc sg' => ['noun-2-neut', 'ἐργ', GrammaticalCase::Vocative, GrammaticalNumber::Singular, 'ἔργον'],
    'ἔργον voc pl' => ['noun-2-neut', 'ἐργ', GrammaticalCase::Vocative, GrammaticalNumber::Plural, 'ἔργα'],
]);

test('the generated form matches the reference grammar', function (
    string $slug,
    string $stem,
    GrammaticalCase $case,
    GrammaticalNumber $number,
    string $referenceForm,
) {
    $generated = (new GenerateInflectedForm)->handle($stem, greekParadigm($slug), $case, $number);

    expect((new GreekFormComparator)->matches($generated, $referenceForm))->toBeTrue(
        "generated [{$generated}] should match [{$referenceForm}] for {$slug} {$case->value} {$number->value}",
    );
})->with('greek noun paradigms');

/**
 * Same golden-dataset contract, for the verb axis (person × number).
 * λύω — regular -ω verb, stem λυ.
 */
dataset('greek verb paradigms', [
    'λύω pres act 1sg' => ['verb-pres-act', 'λυ', GrammaticalPerson::First, GrammaticalNumber::Singular, 'λύω'],
    'λύω pres act 2sg' => ['verb-pres-act', 'λυ', GrammaticalPerson::Second, GrammaticalNumber::Singular, 'λύεις'],
    'λύω pres act 3sg' => ['verb-pres-act', 'λυ', GrammaticalPerson::Third, GrammaticalNumber::Singular, 'λύει'],
    'λύω pres act 1pl' => ['verb-pres-act', 'λυ', GrammaticalPerson::First, GrammaticalNumber::Plural, 'λύομεν'],
    'λύω pres act 2pl' => ['verb-pres-act', 'λυ', GrammaticalPerson::Second, GrammaticalNumber::Plural, 'λύετε'],
    'λύω pres act 3pl' => ['verb-pres-act', 'λυ', GrammaticalPerson::Third, GrammaticalNumber::Plural, 'λύουσιν'],

    'λύω pres mid 1sg' => ['verb-pres-mid', 'λυ', GrammaticalPerson::First, GrammaticalNumber::Singular, 'λύομαι'],
    'λύω pres mid 2sg' => ['verb-pres-mid', 'λυ', GrammaticalPerson::Second, GrammaticalNumber::Singular, 'λύῃ'],
    'λύω pres mid 3sg' => ['verb-pres-mid', 'λυ', GrammaticalPerson::Third, GrammaticalNumber::Singular, 'λύεται'],
    'λύω pres mid 1pl' => ['verb-pres-mid', 'λυ', GrammaticalPerson::First, GrammaticalNumber::Plural, 'λυόμεθα'],
    'λύω pres mid 2pl' => ['verb-pres-mid', 'λυ', GrammaticalPerson::Second, GrammaticalNumber::Plural, 'λύεσθε'],
    'λύω pres mid 3pl' => ['verb-pres-mid', 'λυ', GrammaticalPerson::Third, GrammaticalNumber::Plural, 'λύονται'],

    'λύω pres pass 1sg' => ['verb-pres-pass', 'λυ', GrammaticalPerson::First, GrammaticalNumber::Singular, 'λύομαι'],
    'λύω pres pass 2sg' => ['verb-pres-pass', 'λυ', GrammaticalPerson::Second, GrammaticalNumber::Singular, 'λύῃ'],
    'λύω pres pass 3sg' => ['verb-pres-pass', 'λυ', GrammaticalPerson::Third, GrammaticalNumber::Singular, 'λύεται'],
    'λύω pres pass 1pl' => ['verb-pres-pass', 'λυ', GrammaticalPerson::First, GrammaticalNumber::Plural, 'λυόμεθα'],
    'λύω pres pass 2pl' => ['verb-pres-pass', 'λυ', GrammaticalPerson::Second, GrammaticalNumber::Plural, 'λύεσθε'],
    'λύω pres pass 3pl' => ['verb-pres-pass', 'λυ', GrammaticalPerson::Third, GrammaticalNumber::Plural, 'λύονται'],

    'λύω fut act 1sg' => ['verb-fut-act', 'λυ', GrammaticalPerson::First, GrammaticalNumber::Singular, 'λύσω'],
    'λύω fut act 2sg' => ['verb-fut-act', 'λυ', GrammaticalPerson::Second, GrammaticalNumber::Singular, 'λύσεις'],
    'λύω fut act 3sg' => ['verb-fut-act', 'λυ', GrammaticalPerson::Third, GrammaticalNumber::Singular, 'λύσει'],
    'λύω fut act 1pl' => ['verb-fut-act', 'λυ', GrammaticalPerson::First, GrammaticalNumber::Plural, 'λύσομεν'],
    'λύω fut act 2pl' => ['verb-fut-act', 'λυ', GrammaticalPerson::Second, GrammaticalNumber::Plural, 'λύσετε'],
    'λύω fut act 3pl' => ['verb-fut-act', 'λυ', GrammaticalPerson::Third, GrammaticalNumber::Plural, 'λύσουσιν'],

    'λύω fut mid 1sg' => ['verb-fut-mid', 'λυ', GrammaticalPerson::First, GrammaticalNumber::Singular, 'λύσομαι'],
    'λύω fut mid 2sg' => ['verb-fut-mid', 'λυ', GrammaticalPerson::Second, GrammaticalNumber::Singular, 'λύσῃ'],
    'λύω fut mid 3sg' => ['verb-fut-mid', 'λυ', GrammaticalPerson::Third, GrammaticalNumber::Singular, 'λύσεται'],
    'λύω fut mid 1pl' => ['verb-fut-mid', 'λυ', GrammaticalPerson::First, GrammaticalNumber::Plural, 'λυσόμεθα'],
    'λύω fut mid 2pl' => ['verb-fut-mid', 'λυ', GrammaticalPerson::Second, GrammaticalNumber::Plural, 'λύσεσθε'],
    'λύω fut mid 3pl' => ['verb-fut-mid', 'λυ', GrammaticalPerson::Third, GrammaticalNumber::Plural, 'λύσονται'],
]);

test('the generated verb form matches the reference grammar', function (
    string $slug,
    string $stem,
    GrammaticalPerson $person,
    GrammaticalNumber $number,
    string $referenceForm,
) {
    $generated = (new GenerateInflectedVerbForm)->handle($stem, greekParadigm($slug), $person, $number);

    expect((new GreekFormComparator)->matches($generated, $referenceForm))->toBeTrue(
        "generated [{$generated}] should match [{$referenceForm}] for {$slug} {$person->value} {$number->value}",
    );
})->with('greek verb paradigms');

test('a 3rd-declension nominative singular falls back to the card override', function () {
    $paradigm = greekParadigm('noun-3');

    $withoutOverride = (new GenerateInflectedForm)->handle('σαρκ', $paradigm, GrammaticalCase::Nominative, GrammaticalNumber::Singular);
    $withOverride = (new GenerateInflectedForm)->handle('σαρκ', $paradigm, GrammaticalCase::Nominative, GrammaticalNumber::Singular, 'σάρξ');
    $genitive = (new GenerateInflectedForm)->handle('σαρκ', $paradigm, GrammaticalCase::Genitive, GrammaticalNumber::Singular);

    expect($withoutOverride)->toBe('')
        ->and($withOverride)->toBe('σάρξ')
        ->and($genitive)->toBe('σαρκος');
});
