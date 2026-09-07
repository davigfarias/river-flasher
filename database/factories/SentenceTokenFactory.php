<?php

namespace Database\Factories;

use App\Enums\GrammaticalCase;
use App\Enums\GrammaticalNumber;
use App\Models\Sentence;
use App\Models\SentenceToken;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SentenceToken>
 */
#[UseModel(SentenceToken::class)]
class SentenceTokenFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sentence_id' => Sentence::factory(),
            'position' => $this->faker->numberBetween(0, 5),
            'surface' => $this->faker->word(),
            'card_id' => null,
            'grammatical_case' => null,
            'grammatical_number' => null,
            'is_target' => false,
        ];
    }

    /**
     * A token that can become the drill blank: it's inflected (case +
     * number) and linked to a card.
     */
    public function target(
        GrammaticalCase $case = GrammaticalCase::Dative,
        GrammaticalNumber $number = GrammaticalNumber::Singular,
    ): static {
        return $this->state(fn (array $attributes): array => [
            'grammatical_case' => $case,
            'grammatical_number' => $number,
            'is_target' => true,
        ]);
    }
}
