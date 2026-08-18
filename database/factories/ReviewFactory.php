<?php

namespace Database\Factories;

use App\Enums\ReviewResult;
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
            'result' => $this->faker->randomElement([
                ReviewResult::Remembered,
                ReviewResult::Remembered,
                ReviewResult::Remembered,
                ReviewResult::Forgot,
            ]),
            'reviewed_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ];
    }
}
