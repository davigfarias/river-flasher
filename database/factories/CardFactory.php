<?php

namespace Database\Factories;

use App\Enums\Language;
use App\Models\Card;
use App\Models\Deck;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Card>
 */
#[UseModel(Card::class)]
class CardFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'deck_id' => Deck::factory(),
            'language' => $this->faker->randomElement(Language::cases()),
            'pos' => $this->faker->randomElement(['Noun', 'Verb', 'Adjective', 'Particle']),
            'word' => $this->faker->unique()->word(),
            'transliteration' => $this->faker->word(),
            'definition' => $this->faker->sentence(),
            'example' => $this->faker->sentence(),
            'translation' => $this->faker->sentence(),
            'is_difficult' => false,
            'aced_count' => 0,
            'missed_count' => 0,
            'last_reviewed_at' => null,
        ];
    }

    /**
     * A card the user has been getting right.
     */
    public function studied(): static
    {
        return $this->state(fn (array $attributes): array => [
            'aced_count' => 3,
            'missed_count' => 1,
            'last_reviewed_at' => now()->subHours(2),
        ]);
    }

    /**
     * A card that needs reinforcing: missed more than it's been aced.
     */
    public function struggling(): static
    {
        return $this->state(fn (array $attributes): array => [
            'aced_count' => 1,
            'missed_count' => 3,
            'last_reviewed_at' => now()->subDay(),
        ]);
    }
}
