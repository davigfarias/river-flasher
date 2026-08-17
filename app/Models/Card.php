<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Language;
use Carbon\CarbonImmutable;
use Database\Factories\CardFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $deck_id
 * @property Language $language
 * @property string|null $pos
 * @property string $word
 * @property string|null $transliteration
 * @property string $definition
 * @property string|null $example
 * @property string|null $translation
 * @property bool $is_difficult
 * @property float $ease_factor
 * @property int $interval_minutes
 * @property int $repetitions
 * @property CarbonImmutable|null $due_at
 * @property CarbonImmutable|null $last_reviewed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[UseFactory(CardFactory::class)]
#[Fillable([
    'deck_id',
    'language',
    'pos',
    'word',
    'transliteration',
    'definition',
    'example',
    'translation',
    'is_difficult',
    'ease_factor',
    'interval_minutes',
    'repetitions',
    'due_at',
    'last_reviewed_at',
])]
class Card extends Model
{
    /** @use HasFactory<CardFactory> */
    use HasFactory;

    /**
     * A card is no longer "learning" once its interval reaches one day.
     */
    public const int LEARNING_THRESHOLD_MINUTES = 1440;

    /**
     * A card is considered "mature" once its interval reaches 21 days.
     */
    public const int MATURE_THRESHOLD_MINUTES = 30240;

    public function casts(): array
    {
        return [
            'language' => Language::class,
            'is_difficult' => 'boolean',
            'ease_factor' => 'float',
            'interval_minutes' => 'integer',
            'repetitions' => 'integer',
            'due_at' => 'immutable_datetime',
            'last_reviewed_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<Deck, $this>
     */
    public function deck(): BelongsTo
    {
        return $this->belongsTo(Deck::class);
    }

    /**
     * @return HasMany<Review, $this>
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * @param  Builder<Card>  $query
     * @return Builder<Card>
     */
    public function scopeDue(Builder $query, CarbonImmutable $now): Builder
    {
        return $query->where('due_at', '<=', $now);
    }

    /**
     * @param  Builder<Card>  $query
     * @return Builder<Card>
     */
    public function scopeMature(Builder $query): Builder
    {
        return $query->where('interval_minutes', '>=', self::MATURE_THRESHOLD_MINUTES);
    }

    public function isLearning(): bool
    {
        return $this->interval_minutes < self::LEARNING_THRESHOLD_MINUTES;
    }
}
