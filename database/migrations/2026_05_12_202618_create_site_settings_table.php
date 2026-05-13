<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            // General
            $table->string('site_name')->default('Klivip');
            $table->text('site_description')->nullable();
            $table->string('site_url')->nullable();
            // SEO
            $table->text('meta_keywords')->nullable();
            $table->string('meta_author')->nullable();
            $table->string('tag_manager_id')->nullable();
            $table->string('og_image')->nullable();
            // Contacto
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->text('contact_address')->nullable();
            // Redes Sociales
            $table->string('facebook_url')->nullable();
            $table->string('instagram_url')->nullable();
            $table->string('twitter_url')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->string('youtube_url')->nullable();
            // Personalización
            $table->text('custom_css')->nullable();
            $table->text('header_scripts')->nullable();
            $table->text('footer_scripts')->nullable();
            // Mantenimiento
            $table->boolean('maintenance_mode')->default(false);
            $table->text('maintenance_message')->nullable();
            // Registros
            $table->boolean('enable_registrations')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
