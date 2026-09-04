<?php

use App\Actions\ParseCsvCards;
use App\Enums\Language;

test('it parses english headers', function () {
    $csv = "word,definition,transliteration\nlogos,word,logos\n";

    $parsed = app(ParseCsvCards::class)->handle($csv);

    expect($parsed->missingColumns)->toBe([])
        ->and($parsed->rows)->toHaveCount(1)
        ->and($parsed->rows[0]['word'])->toBe('logos')
        ->and($parsed->rows[0]['definition'])->toBe('word')
        ->and($parsed->rows[0]['transliteration'])->toBe('logos');
});

test('it maps portuguese column titles', function () {
    $csv = "Palavra,Definição,Categoria\nshalom,paz,Saudações\n";

    $parsed = app(ParseCsvCards::class)->handle($csv);

    expect($parsed->missingColumns)->toBe([])
        ->and($parsed->rows[0]['word'])->toBe('shalom')
        ->and($parsed->rows[0]['definition'])->toBe('paz')
        ->and($parsed->rows[0]['category'])->toBe('Saudações');
});

test('it rejects a csv missing a required column', function () {
    $csv = "word,transliteration\nlogos,logos\n";

    $parsed = app(ParseCsvCards::class)->handle($csv);

    expect($parsed->missingColumns)->toBe(['definition'])
        ->and($parsed->rows)->toBe([]);
});

test('it rejects an empty csv', function () {
    $parsed = app(ParseCsvCards::class)->handle('');

    expect($parsed->missingColumns)->not->toBe([])
        ->and($parsed->rows)->toBe([]);
});

test('it strips a utf-8 bom before parsing headers', function () {
    $csv = "\xEF\xBB\xBFword,definition\nlogos,word\n";

    $parsed = app(ParseCsvCards::class)->handle($csv);

    expect($parsed->missingColumns)->toBe([])
        ->and($parsed->rows)->toHaveCount(1);
});

test('it decodes latin-1 encoded content', function () {
    $utf8 = "word,definition\npalavra,tradução em português\n";
    $latin1 = mb_convert_encoding($utf8, 'ISO-8859-1', 'UTF-8');

    $parsed = app(ParseCsvCards::class)->handle($latin1);

    expect($parsed->rows)->toHaveCount(1)
        ->and($parsed->rows[0]['definition'])->toBe('tradução em português');
});

test('it skips rows missing required fields and counts them as errors', function () {
    $csv = "word,definition\nlogos,word\n,missing word\nrhema,\n";

    $parsed = app(ParseCsvCards::class)->handle($csv);

    expect($parsed->rows)->toHaveCount(1)
        ->and($parsed->rowErrors)->toHaveCount(2);
});

test('it detects a single language from the language column', function () {
    $csv = "word,definition,language\nlogos,word,el\nrhema,word,grego\n";

    $parsed = app(ParseCsvCards::class)->handle($csv);

    expect($parsed->languagesMixed)->toBeFalse()
        ->and($parsed->detectedLanguage)->toBe(Language::Greek);
});

test('it flags mixed languages instead of picking one', function () {
    $csv = "word,definition,language\nlogos,word,el\nshalom,paz,he\n";

    $parsed = app(ParseCsvCards::class)->handle($csv);

    expect($parsed->languagesMixed)->toBeTrue()
        ->and($parsed->detectedLanguage)->toBeNull();
});

test('an unrecognized language value is a row error, not a silent guess', function () {
    $csv = "word,definition,language\nlogos,word,klingon\n";

    $parsed = app(ParseCsvCards::class)->handle($csv);

    expect($parsed->rows)->toBe([])
        ->and($parsed->rowErrors)->toHaveCount(1);
});

test('is_difficult accepts common truthy spellings and defaults to false', function () {
    $csv = "word,definition,is_difficult\na,d,true\nb,d,1\nc,d,sim\nd,d,\ne,d,false\n";

    $parsed = app(ParseCsvCards::class)->handle($csv);

    $byWord = collect($parsed->rows)->keyBy('word');

    expect($byWord['a']['isDifficult'])->toBeTrue()
        ->and($byWord['b']['isDifficult'])->toBeTrue()
        ->and($byWord['c']['isDifficult'])->toBeTrue()
        ->and($byWord['d']['isDifficult'])->toBeFalse()
        ->and($byWord['e']['isDifficult'])->toBeFalse();
});
