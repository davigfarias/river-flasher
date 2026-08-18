<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Replaces SM-2 with a plain recall model: how many times a card was
     * remembered vs. forgotten. Backfilled from the existing `reviews`
     * history so cards studied under the old SM-2 ratings keep their
     * standing (good/easy -> aced, again/hard -> missed).
     */
    public function up(): void
    {
        Schema::table('cards', function (Blueprint $table) {
            $table->unsignedInteger('aced_count')->default(0)->after('is_difficult');
            $table->unsignedInteger('missed_count')->default(0)->after('aced_count');
        });

        DB::table('cards')->update([
            'aced_count' => DB::raw("(select count(*) from reviews where reviews.card_id = cards.id and reviews.rating in ('good', 'easy'))"),
            'missed_count' => DB::raw("(select count(*) from reviews where reviews.card_id = cards.id and reviews.rating in ('again', 'hard'))"),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cards', function (Blueprint $table) {
            $table->dropColumn(['aced_count', 'missed_count']);
        });
    }
};
