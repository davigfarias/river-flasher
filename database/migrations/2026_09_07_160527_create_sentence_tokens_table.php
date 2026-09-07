<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per word in a sentence. The token is the axis of the whole
     * system: the blank, the word-bank options, the distractors and the
     * validation all read from here.
     *
     * `case` / `number` are spelled out as `grammatical_case` /
     * `grammatical_number` — `case` is a reserved word in MySQL (prod).
     */
    public function up(): void
    {
        Schema::create('sentence_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sentence_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position');
            $table->string('surface');
            // The lexeme this token is an inflected form of, when known.
            $table->foreignId('card_id')->nullable()->constrained()->nullOnDelete();
            $table->string('grammatical_case', 5)->nullable();   // nom gen dat acc voc
            $table->string('grammatical_number', 2)->nullable(); // sg | pl
            // Whether this token may become the blank in a drill.
            $table->boolean('is_target')->default(false);

            $table->index('sentence_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sentence_tokens');
    }
};
