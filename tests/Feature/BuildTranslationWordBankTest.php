<?php

use App\Actions\BuildTranslationWordBank;
use App\Models\AccessToken;
use App\Models\Card;
use App\Models\Deck;
use Illuminate\Database\Eloquent\Collection;

beforeEach(function () {
    $this->token = AccessToken::factory()->create();
    $this->deck = Deck::factory()->create(['access_token_id' => $this->token->id]);
});

test('the bank contains every correct token plus distractors from the deck', function () {
    $current = Card::factory()->create([
        'deck_id' => $this->deck->id,
        'definition' => 'compaixão',
        'translation' => 'O amor é paciente',
    ]);

    $sibling = Card::factory()->create([
        'deck_id' => $this->deck->id,
        'translation' => 'A esperança nunca falha',
    ]);

    $correct = ['O', 'amor', 'é', 'paciente'];

    $bank = app(BuildTranslationWordBank::class)->handle($correct, new Collection([$current, $sibling]), $current);

    foreach ($correct as $token) {
        expect($bank)->toContain($token);
    }

    expect(count($bank))->toBeGreaterThan(count($correct))
        ->and(array_intersect($bank, ['esperança', 'nunca', 'falha', 'compaixão']))->not->toBeEmpty();
});

test('function words and words already in the answer are never used as distractors', function () {
    $current = Card::factory()->create([
        'deck_id' => $this->deck->id,
        'definition' => 'x',
        'translation' => 'O amor é paciente',
    ]);

    $sibling = Card::factory()->create([
        'deck_id' => $this->deck->id,
        'translation' => 'O amor de Deus',
    ]);

    $correct = ['O', 'amor', 'é', 'paciente'];

    $bank = app(BuildTranslationWordBank::class)->handle($correct, new Collection([$current, $sibling]), $current);

    // "O", "amor" are in the answer; "de" is a stopword — only "Deus" is a valid distractor.
    $distractors = array_values(array_diff($bank, $correct));

    expect($distractors)->toBe(['Deus']);
});
