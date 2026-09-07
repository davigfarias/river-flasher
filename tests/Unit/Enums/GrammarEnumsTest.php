<?php

use App\Enums\Gender;
use App\Enums\GrammaticalCase;
use App\Enums\GrammaticalNumber;
use App\Enums\SentenceSource;
use App\Enums\SentenceStatus;

test('grammatical case maps to its config key and a Portuguese label', function () {
    expect(GrammaticalCase::Dative->value)->toBe('dat')
        ->and(GrammaticalCase::Dative->label())->toBe('dativo')
        ->and(GrammaticalCase::cases())->toHaveCount(5);
});

test('grammatical number maps to its config key and a Portuguese label', function () {
    expect(GrammaticalNumber::Singular->value)->toBe('sg')
        ->and(GrammaticalNumber::Plural->label())->toBe('plural');
});

test('gender maps to its stored value and a Portuguese label', function () {
    expect(Gender::Neuter->value)->toBe('neut')
        ->and(Gender::Feminine->label())->toBe('feminino');
});

test('sentence source and status expose Portuguese labels', function () {
    expect(SentenceSource::Ai->label())->toBe('IA')
        ->and(SentenceStatus::Pending->label())->toBe('pendente')
        ->and(SentenceStatus::Approved->label())->toBe('aprovada');
});
