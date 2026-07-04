<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Crea la tabla sweepstakes para el nuevo sistema de sorteos.
     * Esta tabla reemplaza completamente el sistema anterior de promociones y juegos.
     */
    public function up(): void
    {
        Schema::create('sweepstakes', function (Blueprint $table) {
            $table->id();

            // Relación con site
            $table->foreignId('site_id')
                ->constrained('sites')
                ->onDelete('cascade');

            // Información básica
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();

            // Fechas
            $table->timestamp('starts_at');
            $table->timestamp('expires_at');

            // Límites
            $table->unsignedInteger('max_coupons')->nullable()
                ->comment('Límite total de cupones/participaciones. NULL = sin límite');
            $table->unsignedInteger('max_coupons_per_user')->nullable()
                ->comment('Límite por usuario. NULL = sin límite');

            // Estado
            $table->boolean('is_active')->default(true);
            $table->boolean('is_published')->default(false)
                ->comment('Indica si el sorteo está disponible públicamente');

            // Contador de numeración correlativa
            $table->unsignedInteger('last_coupon_number')->default(0)
                ->comment('Último número de cupón emitido. Se incrementa en cada cobro.');

            // Metadatos
            $table->text('prize_description')->nullable()
                ->comment('Descripción del premio del sorteo');
            $table->timestamp('draw_at')->nullable()
                ->comment('Fecha/hora prevista para el sorteo');
            $table->text('draw_result')->nullable()
                ->comment('Resultado del sorteo (ganadores, observaciones, etc.)');

            // Timestamps y soft deletes
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->unique(['site_id', 'slug']);
            $table->index('site_id');
            $table->index(['starts_at', 'expires_at']);
            $table->index(['is_active', 'is_published']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sweepstakes');
    }
};
