<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Which study mode produced each review. Additive with a 'meaning' default,
 * so every existing row is backfilled to the only mode that existed when it
 * was written. Kept as a short string like the sibling `result` column
 * rather than a DB enum.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->string('mode', 12)->default('meaning')->after('result');
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn('mode');
        });
    }
};
