<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * `access_tokens` is shared across every app in the river series, all
     * pointed at the same production database. The existence guard lets
     * whichever app migrates first create the table without the others
     * colliding on a duplicate `create` when they migrate later.
     */
    public function up(): void
    {
        if (Schema::hasTable('access_tokens')) {
            return;
        }

        Schema::create('access_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('token', 64)->unique();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * Never drops the shared table — another river app may still depend
     * on it.
     */
    public function down(): void {}
};
