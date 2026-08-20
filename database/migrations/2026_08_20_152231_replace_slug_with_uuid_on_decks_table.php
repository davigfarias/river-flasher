<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Decks are now addressed by `/decks/{deck}` as well as `/study/{deck}`,
     * and a slug leaks the deck name into the URL for no benefit — a stable,
     * opaque identifier is the better fit. Existing rows (production runs on
     * a shared MySQL database) are backfilled in place before the column
     * becomes required. Dropping the unique index and the `slug` column are
     * kept as separate steps because SQLite can't drop a column that's
     * still indexed.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('decks', 'uuid')) {
            Schema::table('decks', function (Blueprint $table) {
                $table->uuid('uuid')->nullable()->after('id');
            });
        }

        DB::table('decks')->whereNull('uuid')->pluck('id')->each(
            fn (int $id) => DB::table('decks')->where('id', $id)->update(['uuid' => (string) Str::uuid()]),
        );

        Schema::table('decks', function (Blueprint $table) {
            $table->uuid('uuid')->nullable(false)->change();
        });

        try {
            Schema::table('decks', fn (Blueprint $table) => $table->unique('uuid'));
        } catch (Throwable) {
            // Already added by a previous, partially-failed deploy run.
        }

        if (Schema::hasColumn('decks', 'slug')) {
            Schema::table('decks', function (Blueprint $table) {
                $table->index('access_token_id');
            });

            Schema::table('decks', function (Blueprint $table) {
                $table->dropUnique(['access_token_id', 'slug']);
            });

            Schema::table('decks', function (Blueprint $table) {
                $table->dropColumn('slug');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('decks', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('name');
        });

        DB::table('decks')->orderBy('id')->get()->each(function (object $deck): void {
            $base = Str::slug($deck->name);
            $slug = $base;
            $suffix = 2;

            while (
                DB::table('decks')
                    ->where('access_token_id', $deck->access_token_id)
                    ->where('slug', $slug)
                    ->where('id', '!=', $deck->id)
                    ->exists()
            ) {
                $slug = "{$base}-{$suffix}";
                $suffix++;
            }

            DB::table('decks')->where('id', $deck->id)->update(['slug' => $slug]);
        });

        Schema::table('decks', function (Blueprint $table) {
            $table->string('slug')->nullable(false)->change();
            $table->unique(['access_token_id', 'slug']);
            $table->dropUnique(['uuid']);
        });

        Schema::table('decks', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });
    }
};
