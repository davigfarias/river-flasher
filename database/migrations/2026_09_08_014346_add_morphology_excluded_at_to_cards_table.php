<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Additive, nullable. When set, the card is hidden from the deck
     * morphology annotation screen — the user has decided this word
     * doesn't decline (an adverb, a particle…) and doesn't want it
     * cluttering the list. Null means "still to be annotated".
     */
    public function up(): void
    {
        Schema::table('cards', function (Blueprint $table) {
            $table->timestamp('morphology_excluded_at')->nullable()->after('nom_sg_override');
        });
    }

    public function down(): void
    {
        Schema::table('cards', function (Blueprint $table) {
            $table->dropColumn('morphology_excluded_at');
        });
    }
};
