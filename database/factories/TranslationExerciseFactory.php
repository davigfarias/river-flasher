<?php

namespace Database\Factories;

use App\Models\AccessToken;
use App\Models\Card;
use App\Models\TranslationExercise;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TranslationExercise>
 */
#[UseModel(TranslationExercise::class)]
class TranslationExerciseFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'card_id' => Card::factory(),
            'access_token_id' => AccessToken::factory(),
            'target_text' => 'ἡ ἀγάπη μακροθυμεῖ.',
            'tokens' => ['O', 'amor', 'é', 'paciente'],
        ];
    }
}
