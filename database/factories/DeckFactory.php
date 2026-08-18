<?php

namespace Database\Factories;

use App\Models\AccessToken;
use App\Models\Deck;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Deck>
 */
#[UseModel(Deck::class)]
class DeckFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->word().' '.$this->faker->word().' '.$this->faker->word();

        return [
            'access_token_id' => AccessToken::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
        ];
    }
}
