<?php

use App\Actions\GetParadigm;
use App\Actions\ListParadigms;
use App\Enums\GrammaticalCase;
use App\Enums\GrammaticalNumber;
use App\Models\Card;
use InvalidArgumentException;

test('GetParadigm builds a paradigm from the shipped config', function () {
    $paradigm = (new GetParadigm)->handle('noun-2-masc');

    expect($paradigm->slug)->toBe('noun-2-masc')
        ->and($paradigm->gender)->toBe('masc')
        ->and($paradigm->label)->toContain('2ª declinação');
});

test('GetParadigm rejects an unknown slug', function () {
    (new GetParadigm)->handle('noun-does-not-exist');
})->throws(InvalidArgumentException::class);

test('ListParadigms returns every configured paradigm keyed by slug', function () {
    $paradigms = (new ListParadigms)->handle();

    expect($paradigms)->toHaveKeys(['noun-2-masc', 'noun-2-neut', 'noun-1-fem-eta', 'noun-3'])
        ->and($paradigms->get('noun-3')->endingFor(
            GrammaticalCase::Nominative,
            GrammaticalNumber::Singular,
        ))->toBeNull();
});

test('grammar:paradigm prints a declension table for a seeded card', function () {
    Card::factory()->declinable('λογ', 'noun-2-masc')->create(['word' => 'λόγος']);

    // Form correctness is the golden unit test's job; here we only check
    // the command resolves the card's paradigm/stem and renders.
    $this->artisan('grammar:paradigm', ['lexeme' => 'λόγος'])
        ->expectsOutputToContain('2ª declinação masculina')
        ->assertSuccessful();
});

test('grammar:paradigm fails cleanly when nothing resolves the paradigm', function () {
    $this->artisan('grammar:paradigm', ['lexeme' => 'ghost'])->assertFailed();
});
