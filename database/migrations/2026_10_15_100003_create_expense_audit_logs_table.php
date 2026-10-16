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
        Schema::create('expense_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_id')->nullable()->constrained('expenses')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('voucher_no', 50);
            $table->enum('action', ['created', 'updated', 'deleted']);
            $table->decimal('amount', 10, 2)->nullable();
            $table->date('expense_date')->nullable();
            $table->string('category_name', 100)->nullable();
            $table->string('payment_method', 50)->nullable();
            $table->string('paid_to', 150)->nullable();
            $table->string('operator_name', 100)->nullable();
            $table->string('reason', 255)->nullable();
            $table->json('previous_data')->nullable();
            $table->json('new_data')->nullable();
            $table->timestamps();

            $table->index('voucher_no');
            $table->index('action');
            $table->index('expense_id');
            $table->index('user_id');
            $table->index('expense_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expense_audit_logs');
    }
};
