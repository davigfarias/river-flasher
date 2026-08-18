<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ReviewResult;
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
 * @property ReviewResult $result
 * @property CarbonImmutable $reviewed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[UseFactory(ReviewFactory::class)]
#[Fillable([
    'card_id',
    'access_token_id',
    'result',
    'reviewed_at',
])]
class Review extends Model
{
    /** @use HasFactory<ReviewFactory> */
    use HasFactory;

    public function casts(): array
    {
        return [
            'result' => ReviewResult::class,
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
