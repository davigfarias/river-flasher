<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Per-deck icon/color customization was never worth the picker UI it
     * required — every deck now renders with the same fixed badge.
     */
    public function up(): void
    {
        Schema::table('decks', function (Blueprint $table) {
            $table->dropColumn(['icon', 'color']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('decks', function (Blueprint $table) {
            $table->string('icon')->default('language');
            $table->string('color')->default('text-primary');
        });
    }
};
