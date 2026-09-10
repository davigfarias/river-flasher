<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The cached "banco de frases" for the tradução study mode. One row per card:
 * the target sentence and the ordered list of correct translation tokens,
 * parsed once from the card's own `example` / `translation` fields (no AI).
 *
 * Distractors are NOT stored — they're drawn fresh from the deck each session
 * so the word bank varies. A card's row is rebuilt from scratch when the user
 * hits "resetar banco de frases" on the deck screen, e.g. after editing the
 * card's translation.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('translation_exercises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('card_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('access_token_id')->constrained();
            $table->text('target_text');
            $table->json('tokens');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('translation_exercises');
    }
};
