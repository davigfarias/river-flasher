<?php

namespace Database\Factories;

use App\Enums\CardRating;
use App\Models\AccessToken;
use App\Models\Card;
use App\Models\Review;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
#[UseModel(Review::class)]
class ReviewFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * Callers building realistic history should always pass an explicit
     * `card_id` and `access_token_id` so the review is stamped with the
     * same token that owns the card.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'card_id' => Card::factory(),
            'access_token_id' => AccessToken::factory(),
            'rating' => $this->faker->randomElement([
                CardRating::Good,
                CardRating::Good,
                CardRating::Good,
                CardRating::Easy,
                CardRating::Hard,
                CardRating::Again,
            ]),
            'ease_factor_after' => $this->faker->randomFloat(2, 1.3, 2.8),
            'interval_minutes_after' => $this->faker->numberBetween(1, 40320),
            'reviewed_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ];
    }
}
