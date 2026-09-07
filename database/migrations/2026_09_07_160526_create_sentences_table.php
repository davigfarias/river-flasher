<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Training sentences for the morphology drills. Every sentence enters
     * as `pending` — AI-generated (via the "Gerar frases" screen action)
     * or hand-entered — and is only drawn into a drill once a human
     * approves it.
     */
    public function up(): void
    {
        Schema::create('sentences', function (Blueprint $table) {
            $table->id();
            // Denormalized token scope, same pattern as `reviews`.
            $table->foreignId('access_token_id')->constrained();
            // Which deck's vocabulary this sentence was built from.
            $table->foreignId('deck_id')->nullable()->constrained()->nullOnDelete();
            $table->text('text');
            $table->text('translation_pt');
            $table->string('source', 10);           // manual | ai
            $table->string('status', 10)->default('pending'); // pending | approved | rejected
            $table->string('grammar_focus', 20)->nullable();  // e.g. dat, gen-pl
            $table->timestamps();

            $table->index(['access_token_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sentences');
    }
};
