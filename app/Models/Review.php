<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CardRating;
use Carbon\CarbonImmutable;
use Database\Factories\ReviewFactory;
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
 * @property CardRating $rating
 * @property float $ease_factor_after
 * @property int $interval_minutes_after
 * @property CarbonImmutable $reviewed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[UseFactory(ReviewFactory::class)]
#[Fillable([
    'card_id',
    'access_token_id',
    'rating',
    'ease_factor_after',
    'interval_minutes_after',
    'reviewed_at',
])]
class Review extends Model
{
    /** @use HasFactory<ReviewFactory> */
    use HasFactory;

    public function casts(): array
    {
        return [
            'rating' => CardRating::class,
            'ease_factor_after' => 'float',
            'interval_minutes_after' => 'integer',
            'reviewed_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<Card, $this>
     */
    public function card(): BelongsTo
    {
        return $this->belongsTo(Card::class);
    }

    /**
     * @return BelongsTo<AccessToken, $this>
     */
    public function accessToken(): BelongsTo
    {
        return $this->belongsTo(AccessToken::class);
    }
}
