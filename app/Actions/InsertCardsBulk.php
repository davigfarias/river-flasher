<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\Language;
use App\Models\Card;
use App\Models\Deck;
use Illuminate\Support\Carbon;

final readonly class InsertCardsBulk
{
    /**
     * Mass-inserts parsed CSV rows as cards. Bypasses Eloquent events and
     * automatic timestamps (that's the point of a bulk insert), so
     * created_at/updated_at are stamped manually here.
     *
     * @param  array<int, array{word: string, definition: string, transliteration: ?string, example: ?string, translation: ?string, pos: ?string, category: ?string, isDifficult: bool}>  $rows
     */
    public function handle(Deck $deck, array $rows, Language $language): int
    {
        $now = Carbon::now();

        $records = array_map(fn (array $row): array => [
            'deck_id' => $deck->id,
            'language' => $language->value,
            'pos' => $row['pos'],
            'category' => $row['category'],
            'word' => $row['word'],
            'transliteration' => $row['transliteration'],
            'definition' => $row['definition'],
            'example' => $row['example'],
            'translation' => $row['translation'],
            'image_path' => null,
            'is_image_hidden' => false,
            'is_difficult' => $row['isDifficult'],
            'is_active' => true,
            'aced_count' => 0,
            'missed_count' => 0,
            'last_reviewed_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ], $rows);

        if ($records === []) {
            return 0;
        }

        Card::query()->insert($records);

        return count($records);
    }
}
