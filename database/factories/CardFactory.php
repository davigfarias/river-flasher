<?php

namespace Database\Factories;

use App\Enums\Gender;
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
            'stem' => null,
            'paradigm_slug' => null,
            'gender' => null,
            'nom_sg_override' => null,
            'is_difficult' => false,
            'is_active' => true,
            'aced_count' => 0,
            'missed_count' => 0,
            'last_reviewed_at' => null,
        ];
    }

    /**
     * A Greek card the morphology drills can inflect: `stem` + a paradigm
     * from config/grammar/greek.php. Defaults to λόγος (2nd-decl masc).
     */
    public function declinable(
        string $stem = 'λογ',
        string $paradigmSlug = 'noun-2-masc',
        Gender $gender = Gender::Masculine,
        ?string $nomSgOverride = null,
    ): static {
        return $this->state(fn (array $attributes): array => [
            'language' => Language::Greek,
            'pos' => 'Noun',
            'stem' => $stem,
            'paradigm_slug' => $paradigmSlug,
            'gender' => $gender,
            'nom_sg_override' => $nomSgOverride,
        ]);
    }

    /**
     * A card deactivated from study, e.g. while it's still being edited.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }

    /**
     * A card with an image already stored.
     */
    public function withImage(): static
    {
        return $this->state(fn (array $attributes): array => [
            'image_path' => 'cards/'.$this->faker->uuid().'.webp',
        ]);
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
