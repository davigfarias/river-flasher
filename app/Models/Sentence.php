<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SentenceSource;
use App\Enums\SentenceStatus;
use Database\Factories\SentenceFactory;
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
 * @property int $access_token_id
 * @property int|null $deck_id
 * @property string $text
 * @property string $translation_pt
 * @property SentenceSource $source
 * @property SentenceStatus $status
 * @property string|null $grammar_focus
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[UseFactory(SentenceFactory::class)]
#[Fillable([
    'access_token_id',
    'deck_id',
    'text',
    'translation_pt',
    'source',
    'status',
    'grammar_focus',
])]
class Sentence extends Model
{
    /** @use HasFactory<SentenceFactory> */
    use HasFactory;

    public function casts(): array
    {
        return [
            'source' => SentenceSource::class,
            'status' => SentenceStatus::class,
        ];
    }

    /**
     * @return BelongsTo<AccessToken, $this>
     */
    public function accessToken(): BelongsTo
    {
        return $this->belongsTo(AccessToken::class);
    }

    /**
     * @return BelongsTo<Deck, $this>
     */
    public function deck(): BelongsTo
    {
        return $this->belongsTo(Deck::class);
    }

    /**
     * @return HasMany<SentenceToken, $this>
     */
    public function tokens(): HasMany
    {
        return $this->hasMany(SentenceToken::class)->orderBy('position');
    }

    /**
     * @param  Builder<Sentence>  $query
     * @return Builder<Sentence>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', SentenceStatus::Pending);
    }

    /**
     * @param  Builder<Sentence>  $query
     * @return Builder<Sentence>
     */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', SentenceStatus::Approved);
    }
}
