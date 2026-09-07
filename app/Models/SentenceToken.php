<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\GrammaticalCase;
use App\Enums\GrammaticalNumber;
use Database\Factories\SentenceTokenFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $sentence_id
 * @property int $position
 * @property string $surface
 * @property int|null $card_id
 * @property GrammaticalCase|null $grammatical_case
 * @property GrammaticalNumber|null $grammatical_number
 * @property bool $is_target
 */
#[UseFactory(SentenceTokenFactory::class)]
#[Fillable([
    'sentence_id',
    'position',
    'surface',
    'card_id',
    'grammatical_case',
    'grammatical_number',
    'is_target',
])]
class SentenceToken extends Model
{
    /** @use HasFactory<SentenceTokenFactory> */
    use HasFactory;

    public $timestamps = false;

    public function casts(): array
    {
        return [
            'position' => 'integer',
            'grammatical_case' => GrammaticalCase::class,
            'grammatical_number' => GrammaticalNumber::class,
            'is_target' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Sentence, $this>
     */
    public function sentence(): BelongsTo
    {
        return $this->belongsTo(Sentence::class);
    }

    /**
     * @return BelongsTo<Card, $this>
     */
    public function card(): BelongsTo
    {
        return $this->belongsTo(Card::class);
    }
}
