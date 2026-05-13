<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('settings')->insert([
            [
                'group' => 'app',
                'name' => 'site_name',
                'payload' => json_encode('Klivip'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'group' => 'app',
                'name' => 'site_description',
                'payload' => json_encode('Multi-tenant platform for managing sites'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'group' => 'app',
                'name' => 'enable_registrations',
                'payload' => json_encode(true),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('settings')->where('group', 'app')->delete();
    }
};
