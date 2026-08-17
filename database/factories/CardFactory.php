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
            'ease_factor' => 2.50,
            'interval_minutes' => 0,
            'repetitions' => 0,
            'due_at' => now(),
            'last_reviewed_at' => null,
        ];
    }

    /**
     * A card mid-way through the SM-2 learning steps.
     */
    public function learning(): static
    {
        return $this->state(fn (array $attributes): array => [
            'interval_minutes' => 10,
            'repetitions' => 0,
            'due_at' => now()->addMinutes(10),
            'last_reviewed_at' => now(),
        ]);
    }

    /**
     * A graduated card in the normal review rotation.
     */
    public function review(): static
    {
        return $this->state(fn (array $attributes): array => [
            'interval_minutes' => 4320,
            'repetitions' => 3,
            'due_at' => now()->addDay(),
            'last_reviewed_at' => now()->subDays(2),
        ]);
    }

    /**
     * A card that has crossed the mature threshold (interval >= 21 days).
     */
    public function mature(): static
    {
        return $this->state(fn (array $attributes): array => [
            'interval_minutes' => Card::MATURE_THRESHOLD_MINUTES,
            'repetitions' => 8,
            'due_at' => now()->addDays(21),
            'last_reviewed_at' => now()->subDays(5),
        ]);
    }

    /**
     * Force the card to be due right now, regardless of its SRS state.
     */
    public function dueNow(): static
    {
        return $this->state(fn (array $attributes): array => [
            'due_at' => now(),
        ]);
    }
}
