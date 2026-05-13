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
        Schema::table('games', function (Blueprint $table) {
            $table->dropConstrainedForeignId('site_id');
        });

        Schema::create('game_site', function (Blueprint $table) {
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['game_id', 'site_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_site');

        Schema::table('games', function (Blueprint $table) {
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $table->index(['site_id', 'sort_order']);
        });
    }
};
