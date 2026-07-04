<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Crea la tabla coupon_redemptions para registrar cada evento de cobro
     * con metadata completa de trazabilidad.
     */
    public function up(): void
    {
        Schema::create('coupon_redemptions', function (Blueprint $table) {
            $table->id();

            // Relaciones
            $table->foreignId('sweepstake_id')
                ->constrained('sweepstakes')
                ->onDelete('cascade');
            $table->foreignId('redemption_link_id')
                ->constrained('redemption_links')
                ->onDelete('cascade');
            $table->foreignId('user_id')->nullable()
                ->constrained('users')
                ->onDelete('set null')
                ->comment('Usuario autenticado. NULL si cobrado sin login previo');

            // Identificación del usuario (incluso si no estaba autenticado)
            $table->string('user_email')->nullable();
            $table->string('user_phone', 50)->nullable();
            $table->string('user_name')->nullable();

            // Configuración del cobro
            $table->unsignedInteger('coupon_count')
                ->comment('Cantidad de cupones generados en este cobro');
            $table->unsignedInteger('coupon_start_number')
                ->comment('Número del primer cupón generado');
            $table->unsignedInteger('coupon_end_number')
                ->comment('Número del último cupón generado');

            // Metadata del cobro
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('redemption_channel', 50)->nullable()
                ->comment('Canal del cobro: web, mobile, qr_scan, etc.');
            $table->json('device_info')->nullable();

            // Estado y reversa
            $table->boolean('is_voided')->default(false)
                ->comment('TRUE si este cobro fue anulado/revertido');
            $table->timestamp('voided_at')->nullable();
            $table->text('voided_reason')->nullable();
            $table->foreignId('voided_by')->nullable()
                ->constrained('users')
                ->onDelete('set null')
                ->comment('ID del admin que anuló el cobro');

            // Timestamps
            $table->timestamps();

            // Índices
            $table->index('sweepstake_id');
            $table->index('redemption_link_id');
            $table->index('user_id');
            $table->index('user_email');
            $table->index('is_voided');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupon_redemptions');
    }
};
