<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            if (Schema::hasColumn('site_settings', 'site_url')) {
                $table->dropColumn('site_url');
            }
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('site_settings', 'site_url')) {
                $table->string('site_url')->nullable()->after('site_description');
            }
        });
    }
};
