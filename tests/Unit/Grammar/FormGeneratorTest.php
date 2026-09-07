<?php

use App\Actions\GenerateInflectedForm;
use App\Enums\GrammaticalCase;
use App\Enums\GrammaticalNumber;
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

test('a 3rd-declension nominative singular falls back to the card override', function () {
    $paradigm = greekParadigm('noun-3');

    $withoutOverride = (new GenerateInflectedForm)->handle('σαρκ', $paradigm, GrammaticalCase::Nominative, GrammaticalNumber::Singular);
    $withOverride = (new GenerateInflectedForm)->handle('σαρκ', $paradigm, GrammaticalCase::Nominative, GrammaticalNumber::Singular, 'σάρξ');
    $genitive = (new GenerateInflectedForm)->handle('σαρκ', $paradigm, GrammaticalCase::Genitive, GrammaticalNumber::Singular);

    expect($withoutOverride)->toBe('')
        ->and($withOverride)->toBe('σάρξ')
        ->and($genitive)->toBe('σαρκος');
});
