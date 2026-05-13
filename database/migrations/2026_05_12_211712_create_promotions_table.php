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
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('offer_label');
            $table->text('description')->nullable();
            $table->string('scope')->default('site')->index();
            $table->string('schedule_type')->default('standard')->index();
            $table->json('recurrent_days')->nullable();
            $table->date('special_date')->nullable()->index();
            $table->timestamp('starts_at')->nullable()->index();
            $table->timestamp('ends_at')->nullable()->index();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->index(['scope', 'is_active']);
            $table->index(['site_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};
