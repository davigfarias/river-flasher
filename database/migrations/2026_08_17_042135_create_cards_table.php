<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deck_id')->constrained()->cascadeOnDelete();

            // Content
            $table->string('language', 2);
            $table->string('pos', 50)->nullable();
            $table->string('word');
            $table->string('transliteration')->nullable();
            $table->text('definition');
            $table->text('example')->nullable();
            $table->text('translation')->nullable();
            $table->boolean('is_difficult')->default(false);

            // SM-2 spaced-repetition state
            $table->decimal('ease_factor', 4, 2)->default(2.50);
            $table->unsignedInteger('interval_minutes')->default(0);
            $table->unsignedInteger('repetitions')->default(0);
            $table->dateTime('due_at')->nullable();
            $table->dateTime('last_reviewed_at')->nullable();

            $table->timestamps();

            $table->index(['deck_id', 'due_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cards');
    }
};
