<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Crea la tabla sweepstake_draws que registra cada sorteo realizado
     * sobre los cupones válidos de una sweepstake, junto con la pivot
     * sweepstake_draw_coupon que vincula el sorteo con los cupones ganadores.
     */
    public function up(): void
    {
        Schema::create('sweepstake_draws', function (Blueprint $table) {
            $table->id();

            // Relaciones
            $table->foreignId('sweepstake_id')
                ->constrained('sweepstakes')
                ->onDelete('cascade');
            $table->foreignId('drawn_by')->nullable()
                ->constrained('users')
                ->onDelete('set null');

            // Datos del sorteo
            $table->unsignedSmallInteger('winners_count')
                ->comment('Cantidad de ganadores seleccionados en este sorteo');
            $table->text('notes')->nullable()
                ->comment('Observaciones del operador sobre el sorteo');
            $table->timestamp('drawn_at')
                ->comment('Fecha/hora en la que se realizó el sorteo');
            $table->boolean('notified')
                ->default(false)
                ->comment('Indica si se despacharon las notificaciones a ganadores');

            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('sweepstake_id');
            $table->index('drawn_at');
            $table->index('drawn_by');
        });

        Schema::create('sweepstake_draw_coupon', function (Blueprint $table) {
            $table->id();

            $table->foreignId('sweepstake_draw_id')
                ->constrained('sweepstake_draws')
                ->onDelete('cascade');
            $table->foreignId('sweepstake_coupon_id')
                ->constrained('sweepstake_coupons')
                ->onDelete('cascade');
            $table->foreignId('user_id')->nullable()
                ->constrained('users')
                ->onDelete('set null');

            // Posición del ganador dentro del sorteo (1 = primer premio)
            $table->unsignedSmallInteger('position');

            $table->timestamps();

            // Restricciones
            $table->unique(['sweepstake_draw_id', 'sweepstake_coupon_id'], 'sdc_draw_coupon_unique');
            $table->unique(['sweepstake_draw_id', 'position'], 'sdc_draw_position_unique');

            // Índices
            $table->index('sweepstake_coupon_id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sweepstake_draw_coupon');
        Schema::dropIfExists('sweepstake_draws');
    }
};
