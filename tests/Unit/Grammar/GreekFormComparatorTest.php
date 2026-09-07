<?php

use App\Support\Grammar\GreekFormComparator;

test('forms that differ only in diacritics, case or final sigma match', function (string $a, string $b) {
    expect((new GreekFormComparator)->matches($a, $b))->toBeTrue();
})->with([
    'accent placement' => ['ανθρωπου', 'ἀνθρώπου'],
    'breathing' => ['εργον', 'ἔργον'],
    'iota subscript' => ['λογω', 'λόγῳ'],
    'capitalisation' => ['Λόγος', 'λόγος'],
    'final vs medial sigma' => ['λογος', 'λόγοσ'],
    'surrounding punctuation' => ['ἀνθρώπῳ.', 'ανθρωπω'],
]);

test('forms with a different case ending do not match', function (string $a, string $b) {
    expect((new GreekFormComparator)->matches($a, $b))->toBeFalse();
})->with([
    'dative vs genitive' => ['λόγῳ', 'λόγου'],
    'singular vs plural' => ['λόγος', 'λόγοι'],
    'different lexeme' => ['λόγος', 'γραφή'],
]);

test('two empty forms never match', function () {
    expect((new GreekFormComparator)->matches('', ''))->toBeFalse()
        ->and((new GreekFormComparator)->matches('  .  ', ''))->toBeFalse();
});
