<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Crea la tabla redemption_sources para definir los tipos de orígenes de canje
     * (QR, link, manual, API).
     */
    public function up(): void
    {
        Schema::create('redemption_sources', function (Blueprint $table) {
            $table->id();

            // Identificación
            $table->string('code', 50)->unique()
                ->comment('Código único del tipo (ej: qr, link, manual, api)');
            $table->string('name');
            $table->text('description')->nullable();

            // Estado
            $table->boolean('is_active')->default(true);

            // Timestamps
            $table->timestamps();

            // Índices
            $table->index('code');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('redemption_sources');
    }
};
