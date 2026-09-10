<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Additive, all defaulting to 0. The study session now has three modes
 * (significado / leitura / tradução); each grades onto its own counter pair
 * so the "needs reinforcing" logic on the existing `aced_count`/`missed_count`
 * columns keeps meaning exactly what it did before. Existing rows get 0s,
 * which is the correct starting point for a mode never practised yet.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cards', function (Blueprint $table) {
            $table->unsignedInteger('reading_aced_count')->default(0)->after('missed_count');
            $table->unsignedInteger('reading_missed_count')->default(0)->after('reading_aced_count');
            $table->unsignedInteger('translation_aced_count')->default(0)->after('reading_missed_count');
            $table->unsignedInteger('translation_missed_count')->default(0)->after('translation_aced_count');
        });
    }

    public function down(): void
    {
        Schema::table('cards', function (Blueprint $table) {
            $table->dropColumn([
                'reading_aced_count',
                'reading_missed_count',
                'translation_aced_count',
                'translation_missed_count',
            ]);
        });
    }
};
