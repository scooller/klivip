<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Crea la tabla redemption_links para QRs/links/packs específicos.
     */
    public function up(): void
    {
        Schema::create('redemption_links', function (Blueprint $table) {
            $table->id();

            // Relaciones
            $table->foreignId('sweepstake_id')
                ->constrained('sweepstakes')
                ->onDelete('cascade');
            $table->foreignId('redemption_source_id')
                ->constrained('redemption_sources')
                ->onDelete('restrict');

            // Identificación
            $table->string('code', 100)->unique()
                ->comment('Código único del link/QR (ej: UUID o slug generado)');
            $table->string('title');
            $table->text('description')->nullable();

            // Configuración del pack
            $table->unsignedInteger('coupon_count')->default(1)
                ->comment('Cantidad de cupones que genera este pack');

            // Vigencia del link (puede ser más restrictiva que el sorteo)
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_until')->nullable();
            $table->unsignedInteger('max_redemptions')->nullable()
                ->comment('Límite de veces que se puede canjear este link. NULL = sin límite');

            // Estado
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('redemption_count')->default(0)
                ->comment('Contador de cuántas veces se canjeó este link');

            // Timestamps y soft deletes
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('sweepstake_id');
            $table->index('redemption_source_id');
            $table->index(['valid_from', 'valid_until']);
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('redemption_links');
    }
};
