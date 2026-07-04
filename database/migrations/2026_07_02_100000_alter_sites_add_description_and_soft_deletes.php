<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Modifica la tabla sites existente para agregar soporte completo.
     */
    public function up(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            // Agregar description
            $table->text('description')->nullable()->after('slug');

            // Agregar soft deletes
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn('description');
        });
    }
};
