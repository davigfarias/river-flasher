<?php

use App\Actions\TokenizeText;

$tokenize = fn (string $text) => app(TokenizeText::class)->handle($text);

test('it splits on whitespace and strips surrounding punctuation', function () use ($tokenize) {
    expect($tokenize('  O amor, é paciente.  '))->toBe(['O', 'amor', 'é', 'paciente']);
});

test('it keeps word-internal marks and casing', function () use ($tokenize) {
    expect($tokenize("d'água meia-noite"))->toBe(["d'água", 'meia-noite']);
});

test('an empty string yields no tokens', function () use ($tokenize) {
    expect($tokenize('   '))->toBe([]);
});
