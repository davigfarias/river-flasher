<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Additive only. Every column is nullable — production `cards` has
     * months of data, and a card without these fields simply doesn't
     * enter the morphology drills. Nothing existing changes.
     */
    public function up(): void
    {
        Schema::table('cards', function (Blueprint $table) {
            $table->string('stem')->nullable()->after('category');
            $table->string('paradigm_slug')->nullable()->after('stem');
            $table->string('gender', 10)->nullable()->after('paradigm_slug');
            $table->string('nom_sg_override')->nullable()->after('gender');
        });
    }

    public function down(): void
    {
        Schema::table('cards', function (Blueprint $table) {
            $table->dropColumn(['stem', 'paradigm_slug', 'gender', 'nom_sg_override']);
        });
    }
};
