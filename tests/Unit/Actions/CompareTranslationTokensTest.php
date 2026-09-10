<?php

use App\Actions\CompareTranslationTokens;

$compare = fn (array $a, array $b) => app(CompareTranslationTokens::class)->handle($a, $b);

test('an exact token sequence matches', function () use ($compare) {
    expect($compare(['O', 'amor', 'é', 'paciente'], ['O', 'amor', 'é', 'paciente']))->toBeTrue();
});

test('casing and surrounding punctuation are ignored', function () use ($compare) {
    expect($compare(['o', 'AMOR', 'é', 'paciente'], ['O', 'amor', 'é', 'paciente,']))->toBeTrue();
});

test('word order matters', function () use ($compare) {
    expect($compare(['amor', 'O', 'é', 'paciente'], ['O', 'amor', 'é', 'paciente']))->toBeFalse();
});

test('a missing or extra word fails', function () use ($compare) {
    expect($compare(['O', 'amor', 'paciente'], ['O', 'amor', 'é', 'paciente']))->toBeFalse()
        ->and($compare(['O', 'amor', 'é', 'muito', 'paciente'], ['O', 'amor', 'é', 'paciente']))->toBeFalse();
});

test('accents are significant', function () use ($compare) {
    expect($compare(['e'], ['é']))->toBeFalse();
});

test('two empty sequences do not count as a match', function () use ($compare) {
    expect($compare([], []))->toBeFalse();
});
