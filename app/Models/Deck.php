<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\DeckFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $access_token_id
 * @property string $name
 * @property string $slug
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[UseFactory(DeckFactory::class)]
#[Fillable(['access_token_id', 'name', 'slug'])]
class Deck extends Model
{
    /** @use HasFactory<DeckFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<AccessToken, $this>
     */
    public function accessToken(): BelongsTo
    {
        return $this->belongsTo(AccessToken::class);
    }

    /**
     * @return HasMany<Card, $this>
     */
    public function cards(): HasMany
    {
        return $this->hasMany(Card::class);
    }
}
