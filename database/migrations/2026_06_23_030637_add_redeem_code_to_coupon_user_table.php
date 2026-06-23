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
        Schema::table('coupon_user', function (Blueprint $table) {
            $table->string('redeem_code', 24)->nullable()->unique()->after('redeemed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('coupon_user', function (Blueprint $table) {
            $table->dropColumn('redeem_code');
        });
    }
};
