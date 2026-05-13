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
        if (! Schema::hasTable('game_site') || Schema::hasColumn('game_site', 'sort_order')) {
            return;
        }

        Schema::table('game_site', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->default(0)->after('site_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('game_site') || ! Schema::hasColumn('game_site', 'sort_order')) {
            return;
        }

        Schema::table('game_site', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }
};
