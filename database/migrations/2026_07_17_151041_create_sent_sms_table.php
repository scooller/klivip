<?php

declare(strict_types=1);

use App\Models\SmsTemplate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sent_sms', function (Blueprint $table) {
            $table->id();

            // Template reference
            $table->foreignIdFor(SmsTemplate::class)
                ->nullable()
                ->constrained('sms_templates')
                ->nullOnDelete();

            // Recipient & sender
            $table->string('to', 50)->index();
            $table->string('from', 50)->nullable();

            // Content
            $table->string('subject', 100)->nullable();
            $table->text('body');

            // Status tracking
            $table->unsignedTinyInteger('status')->default(2)->index();
            $table->dateTime('sent_at')->nullable()->index();
            $table->json('metadata')->nullable();

            // Error log (if failed)
            $table->text('error_message')->nullable();

            // Who sent it
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();

            // Polymorphic: what model this SMS relates to
            $table->nullableMorphs('sendable');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sent_sms');
    }
};
