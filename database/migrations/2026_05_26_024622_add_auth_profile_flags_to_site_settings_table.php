<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->boolean('enable_home_login_without_code')->default(false)->after('enable_registrations');
            $table->boolean('enable_profile_unlock_otp')->default(true)->after('enable_home_login_without_code');
            $table->boolean('enable_profile_unlock_magic_link')->default(true)->after('enable_profile_unlock_otp');
            $table->boolean('hide_birth_date_on_profile')->default(true)->after('enable_profile_unlock_magic_link');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn([
                'enable_home_login_without_code',
                'enable_profile_unlock_otp',
                'enable_profile_unlock_magic_link',
                'hide_birth_date_on_profile',
            ]);
        });
    }
};
