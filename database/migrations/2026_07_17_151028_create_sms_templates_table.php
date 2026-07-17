<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_templates', function (Blueprint $table) {
            $table->id();

            $table->string('key', 100)->unique();
            $table->json('name');
            $table->string('category', 100)->default('transactional')->index();

            // Content (translatable — stored as JSON: {"es": "...", "en": "..."})
            $table->json('body');

            // Token documentation: {"name": "string", "coupon_count": "int"}
            $table->json('token_schema')->nullable();

            // Sender name shown in the SMS (e.g. "Klivip")
            $table->string('sender_name', 50)->nullable();

            // State
            $table->boolean('is_active')->default(true);
            $table->boolean('is_locked')->default(false);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_templates');
    }
};
