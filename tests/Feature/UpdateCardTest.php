<?php

use App\Actions\UpdateCard;
use App\DTO\CardData;
use App\Enums\Language;
use App\Models\Card;

test('it updates every editable field of the card', function () {
    $card = Card::factory()->create(['language' => Language::Greek, 'word' => 'λόγος']);

    $updated = app(UpdateCard::class)->handle($card, new CardData(
        language: Language::Greek,
        word: 'χάρις',
        transliteration: 'cháris',
        definition: 'Grace.',
        example: 'τῇ γὰρ χάριτί ἐστε σεσῳσμένοι',
        translation: 'For by grace you have been saved.',
        pos: 'Noun',
        isDifficult: true,
        imagePath: 'cards/example.webp',
    ));

    expect($updated->word)->toBe('χάρις')
        ->and($updated->transliteration)->toBe('cháris')
        ->and($updated->definition)->toBe('Grace.')
        ->and($updated->example)->toBe('τῇ γὰρ χάριτί ἐστε σεσῳσμένοι')
        ->and($updated->translation)->toBe('For by grace you have been saved.')
        ->and($updated->pos)->toBe('Noun')
        ->and($updated->is_difficult)->toBeTrue()
        ->and($updated->image_path)->toBe('cards/example.webp');
});
