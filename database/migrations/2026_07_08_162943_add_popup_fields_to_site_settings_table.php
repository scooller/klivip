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
        Schema::table('site_settings', function (Blueprint $table) {
            $table->boolean('popup_enabled')->default(false)->after('hide_birth_date_on_profile');
            $table->string('popup_image')->nullable()->after('popup_enabled');
            $table->string('popup_link')->nullable()->after('popup_image');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['popup_enabled', 'popup_image', 'popup_link']);
        });
    }
};
