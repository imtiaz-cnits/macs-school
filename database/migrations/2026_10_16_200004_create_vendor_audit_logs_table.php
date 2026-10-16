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
        Schema::create('vendor_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->nullable()->constrained('expense_vendors')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('operator_name', 150)->nullable();
            $table->string('action', 50); // 'opening_balance_created', 'payment_recorded', 'bill_adjusted'
            $table->string('entity_type', 100);
            $table->unsignedBigInteger('entity_id');
            $table->decimal('amount', 12, 2)->nullable();
            $table->string('reason', 255)->nullable();
            $table->json('previous_data')->nullable();
            $table->json('new_data')->nullable();
            $table->timestamps();

            $table->index('vendor_id');
            $table->index('user_id');
            $table->index(['entity_type', 'entity_id']);
            $table->index('action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_audit_logs');
    }
};
