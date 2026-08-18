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
 * @property int $aced_count
 * @property int $missed_count
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
    'aced_count',
    'missed_count',
    'last_reviewed_at',
])]
class Card extends Model
{
    /** @use HasFactory<CardFactory> */
    use HasFactory;

    public function casts(): array
    {
        return [
            'language' => Language::class,
            'is_difficult' => 'boolean',
            'aced_count' => 'integer',
            'missed_count' => 'integer',
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
     * A card "needs reinforcing" once it's been missed more than it's been
     * aced. Using a comparison rather than `missed_count > 0` lets a card
     * work its way back off the reinforce list as it's answered correctly.
     *
     * @param  Builder<Card>  $query
     * @return Builder<Card>
     */
    public function scopeToReinforce(Builder $query): Builder
    {
        return $query->whereColumn('missed_count', '>', 'aced_count');
    }
}
