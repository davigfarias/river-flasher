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
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('card_id')->constrained()->cascadeOnDelete();
            // Denormalized for cheap dashboard queries scoped by token.
            $table->foreignId('access_token_id')->constrained();
            $table->string('rating', 10);
            $table->decimal('ease_factor_after', 4, 2);
            $table->unsignedInteger('interval_minutes_after');
            $table->dateTime('reviewed_at');
            $table->timestamps();

            $table->index(['access_token_id', 'reviewed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
