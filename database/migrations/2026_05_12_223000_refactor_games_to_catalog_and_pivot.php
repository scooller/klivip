<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('games', 'site_id')) {
            Schema::table('games', function (Blueprint $table) {
                $table->dropConstrainedForeignId('site_id');
            });
        }

        $this->dropGamesSiteIdSortOrderIndex();

        if (! Schema::hasTable('game_site')) {
            Schema::create('game_site', function (Blueprint $table) {
                $table->foreignId('game_id')->constrained()->cascadeOnDelete();
                $table->foreignId('site_id')->constrained()->cascadeOnDelete();
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();

                $table->unique(['game_id', 'site_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('game_site');

        if (! Schema::hasColumn('games', 'site_id')) {
            Schema::table('games', function (Blueprint $table) {
                $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            });
        }

        $this->dropGamesSiteIdSortOrderIndex();

        $indexExists = collect(DB::select('SHOW INDEX FROM games'))
            ->pluck('Key_name')
            ->contains('games_site_id_sort_order_index');

        if (! $indexExists) {
            Schema::table('games', function (Blueprint $table) {
                $table->index(['site_id', 'sort_order']);
            });
        }
    }

    private function dropGamesSiteIdSortOrderIndex(): void
    {
        $indexExists = collect(DB::select('SHOW INDEX FROM games'))
            ->pluck('Key_name')
            ->contains('games_site_id_sort_order_index');

        if ($indexExists) {
            Schema::table('games', function (Blueprint $table) {
                $table->dropIndex('games_site_id_sort_order_index');
            });
        }
    }
};
