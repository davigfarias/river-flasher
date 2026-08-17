<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Redundant with the card's own `language` — a deck can (and does)
     * mix Greek and Hebrew cards, so a deck-level language never held
     * meaning. Guarded so this is a no-op on databases where the column
     * was already removed from the create migration.
     */
    public function up(): void
    {
        if (Schema::hasColumn('decks', 'language')) {
            Schema::table('decks', function (Blueprint $table) {
                $table->dropColumn('language');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('decks', 'language')) {
            Schema::table('decks', function (Blueprint $table) {
                $table->string('language', 2)->default('el')->after('slug');
            });
        }
    }
};
