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
     * Adds the simplified remembered/forgot result. Nullable first because
     * `reviews` already has rows in production; backfilled from the old
     * `rating` column, then locked to NOT NULL.
     */
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->string('result', 10)->nullable()->after('rating');
        });

        DB::table('reviews')->whereIn('rating', ['good', 'easy'])->update(['result' => 'remembered']);
        DB::table('reviews')->whereIn('rating', ['again', 'hard'])->update(['result' => 'forgot']);

        Schema::table('reviews', function (Blueprint $table) {
            $table->string('result', 10)->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn('result');
        });
    }
};
