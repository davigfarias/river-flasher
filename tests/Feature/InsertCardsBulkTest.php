<?php

use App\Actions\InsertCardsBulk;
use App\Enums\Language;
use App\Models\Card;
use App\Models\Deck;

test('it bulk inserts cards with new-card defaults', function () {
    $deck = Deck::factory()->create();

    $rows = [
        ['word' => 'logos', 'definition' => 'word', 'transliteration' => 'lógos', 'example' => null, 'translation' => null, 'pos' => 'Substantivo', 'category' => null, 'isDifficult' => false],
        ['word' => 'rhema', 'definition' => 'utterance', 'transliteration' => null, 'example' => null, 'translation' => null, 'pos' => null, 'category' => null, 'isDifficult' => true],
    ];

    $count = app(InsertCardsBulk::class)->handle($deck, $rows, Language::Greek);

    expect($count)->toBe(2)
        ->and(Card::where('deck_id', $deck->id)->count())->toBe(2);

    $card = Card::where('word', 'logos')->sole();

    expect($card->language)->toBe(Language::Greek)
        ->and($card->image_path)->toBeNull()
        ->and($card->is_active)->toBeTrue()
        ->and($card->is_difficult)->toBeFalse()
        ->and($card->aced_count)->toBe(0)
        ->and($card->missed_count)->toBe(0)
        ->and($card->last_reviewed_at)->toBeNull()
        ->and($card->created_at)->not->toBeNull();

    expect(Card::where('word', 'rhema')->sole()->is_difficult)->toBeTrue();
});

test('it returns zero for an empty row list without querying', function () {
    $deck = Deck::factory()->create();

    $count = app(InsertCardsBulk::class)->handle($deck, [], Language::Hebrew);

    expect($count)->toBe(0)
        ->and(Card::where('deck_id', $deck->id)->count())->toBe(0);
});
