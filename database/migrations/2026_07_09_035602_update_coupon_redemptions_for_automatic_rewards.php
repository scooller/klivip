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
        Schema::table('coupon_redemptions', function (Blueprint $table) {
            $table->foreignId('redemption_link_id')->nullable()->change();
            $table->foreignId('automatic_reward_id')->nullable()->after('redemption_link_id')->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('coupon_redemptions', function (Blueprint $table) {
            $table->dropForeign(['automatic_reward_id']);
            $table->dropColumn('automatic_reward_id');
            // Reverting redemption_link_id to not nullable could fail if there are nulls, but for rollback it is:
            $table->foreignId('redemption_link_id')->nullable(false)->change();
        });
    }
};
