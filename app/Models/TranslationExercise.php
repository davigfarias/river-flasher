<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\TranslationExerciseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $card_id
 * @property int $access_token_id
 * @property string $target_text
 * @property array<int, string> $tokens
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[UseFactory(TranslationExerciseFactory::class)]
#[Fillable([
    'card_id',
    'access_token_id',
    'target_text',
    'tokens',
])]
class TranslationExercise extends Model
{
    /** @use HasFactory<TranslationExerciseFactory> */
    use HasFactory;

    public function casts(): array
    {
        return [
            'tokens' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Card, $this>
     */
    public function card(): BelongsTo
    {
        return $this->belongsTo(Card::class);
    }
}
