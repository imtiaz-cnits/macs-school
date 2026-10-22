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
        Schema::create('sms_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('schedule_uuid', 64)->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title')->nullable();
            $table->text('message');
            $table->string('recipient_source', 30)->default('students');
            $table->json('filter_criteria')->nullable();
            $table->json('recipient_snapshot')->nullable();
            $table->dateTime('scheduled_at');
            $table->string('status', 30)->default('pending');
            $table->unsignedInteger('total_recipients')->default(0);
            $table->unsignedInteger('total_segments')->default(1);
            $table->string('encoding', 20)->default('GSM');
            $table->decimal('estimated_cost', 10, 2)->default(0.00);
            $table->decimal('actual_cost', 10, 2)->nullable();
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->dateTime('dispatched_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['status', 'scheduled_at']);
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_schedules');
    }
};
