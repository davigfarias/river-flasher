<?php

use App\Enums\Language;

test('each language has a Portuguese label', function () {
    expect(Language::Greek->label())->toBe('Grego')
        ->and(Language::Hebrew->label())->toBe('Hebraico');
});
