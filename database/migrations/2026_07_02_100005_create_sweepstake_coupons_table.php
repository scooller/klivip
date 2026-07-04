<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Crea la tabla sweepstake_coupons para participaciones individuales.
     * NOTA: Se crea como sweepstake_coupons para no confligir con la tabla coupons antigua.
     * En Fase 7 se eliminará la tabla coupons antigua y se renombrará esta a coupons.
     */
    public function up(): void
    {
        Schema::create('sweepstake_coupons', function (Blueprint $table) {
            $table->id();

            // Relaciones
            $table->foreignId('sweepstake_id')
                ->constrained('sweepstakes')
                ->onDelete('cascade');
            $table->foreignId('redemption_id')
                ->constrained('coupon_redemptions')
                ->onDelete('cascade');
            $table->foreignId('user_id')->nullable()
                ->constrained('users')
                ->onDelete('set null');

            // Numeración
            $table->unsignedInteger('coupon_number')
                ->comment('Número correlativo ÚNICO dentro del sorteo');

            // Estado de anulación
            $table->boolean('is_voided')->default(false)
                ->comment('TRUE si este cupón fue anulado (no participa)');
            $table->timestamp('voided_at')->nullable();
            $table->text('voided_reason')->nullable();
            $table->foreignId('voided_by')->nullable()
                ->constrained('users')
                ->onDelete('set null');

            // Auditoría de uso
            $table->boolean('is_used')->default(false)
                ->comment('TRUE si este cupón fue seleccionado/ganador en el sorteo');
            $table->timestamp('used_at')->nullable();
            $table->foreignId('used_by')->nullable()
                ->constrained('users')
                ->onDelete('set null')
                ->comment('ID del admin que marcó el cupón como usado');

            // Timestamps y soft deletes
            $table->timestamps();
            $table->softDeletes();

            // Restricción de unicidad por sorteo y número
            $table->unique(['sweepstake_id', 'coupon_number']);

            // Índices
            $table->index('sweepstake_id');
            $table->index('redemption_id');
            $table->index('user_id');
            $table->index('is_voided');
            $table->index('is_used');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sweepstake_coupons');
    }
};
