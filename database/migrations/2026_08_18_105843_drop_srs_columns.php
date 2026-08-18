<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Drops the SM-2 columns now that `cards.aced_count`/`missed_count`
     * and `reviews.result` have been backfilled by the two prior
     * migrations. Kept as its own file so a deploy can pause between the
     * backfill and this irreversible drop to spot-check production data.
     * `cards.last_reviewed_at` is kept — it still drives deck ordering and
     * the "reviewed today" dashboard stat.
     *
     * `cards_deck_id_due_at_index` is the only index covering `deck_id`,
     * which MySQL's InnoDB requires to stay indexed for the `deck_id`
     * foreign key — dropping it outright errors with 1553. Adding a plain
     * index on `deck_id` in the same statement keeps the FK covered
     * throughout. SQLite has no such requirement, which is why this only
     * surfaces against MySQL (i.e. never appeared in local/test runs).
     */
    public function up(): void
    {
        Schema::table('cards', function (Blueprint $table) {
            $table->index('deck_id');
            $table->dropIndex(['deck_id', 'due_at']);
            $table->dropColumn(['ease_factor', 'interval_minutes', 'repetitions', 'due_at']);
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn(['rating', 'ease_factor_after', 'interval_minutes_after']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * Recreates empty columns with their original defaults — the SM-2
     * data itself is not recoverable once dropped.
     */
    public function down(): void
    {
        Schema::table('cards', function (Blueprint $table) {
            $table->decimal('ease_factor', 4, 2)->default(2.50);
            $table->unsignedInteger('interval_minutes')->default(0);
            $table->unsignedInteger('repetitions')->default(0);
            $table->dateTime('due_at')->nullable();
            $table->index(['deck_id', 'due_at']);
            $table->dropIndex(['deck_id']);
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->string('rating', 10)->default('good');
            $table->decimal('ease_factor_after', 4, 2)->default(2.50);
            $table->unsignedInteger('interval_minutes_after')->default(0);
        });
    }
};
