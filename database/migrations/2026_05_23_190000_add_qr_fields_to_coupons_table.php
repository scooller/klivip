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
        Schema::table('coupons', function (Blueprint $table): void {
            $table->boolean('qr_enabled')->default(false)->after('is_active');
            $table->string('qr_token', 80)->nullable()->unique()->after('qr_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('coupons', function (Blueprint $table): void {
            $table->dropUnique('coupons_qr_token_unique');
            $table->dropColumn(['qr_enabled', 'qr_token']);
        });
    }
};
