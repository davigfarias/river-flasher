<?php

namespace Database\Factories;

use App\Enums\SentenceSource;
use App\Enums\SentenceStatus;
use App\Models\AccessToken;
use App\Models\Deck;
use App\Models\Sentence;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sentence>
 */
#[UseModel(Sentence::class)]
class SentenceFactory extends Factory
{
    /**
     * Callers building realistic data should pass an explicit `deck_id`
     * and `access_token_id` so the sentence is scoped to the same token
     * that owns the deck.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'access_token_id' => AccessToken::factory(),
            'deck_id' => Deck::factory(),
            'text' => 'ὁ ἀπόστολος γράφει τῷ ἀνθρώπῳ',
            'translation_pt' => 'o apóstolo escreve ao homem',
            'source' => SentenceSource::Ai,
            'status' => SentenceStatus::Pending,
            'grammar_focus' => 'dat',
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => SentenceStatus::Approved,
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => SentenceStatus::Rejected,
        ]);
    }

    public function manual(): static
    {
        return $this->state(fn (array $attributes): array => [
            'source' => SentenceSource::Manual,
        ]);
    }
}
