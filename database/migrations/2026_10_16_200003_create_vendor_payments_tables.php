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
        Schema::create('vendor_payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_no', 50)->unique();
            $table->foreignId('vendor_id')->constrained('expense_vendors')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('expense_id')->nullable()->constrained('expenses')->nullOnDelete();
            $table->date('payment_date');
            $table->string('payment_method', 50);
            $table->decimal('amount', 12, 2);
            $table->string('reference_no', 100)->nullable();
            $table->string('receipt_file', 255)->nullable();
            $table->text('note')->nullable();
            $table->string('created_from', 50)->default('direct'); // 'direct', 'voucher'
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('vendor_id');
            $table->index('branch_id');
            $table->index('payment_date');
            $table->index('payment_method');
            $table->index('created_from');
        });

        Schema::create('vendor_payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_payment_id')->constrained('vendor_payments')->cascadeOnDelete();
            $table->string('allocatable_type', 100);
            $table->unsignedBigInteger('allocatable_id');
            $table->decimal('allocated_amount', 12, 2);
            $table->string('note', 255)->nullable();
            $table->timestamps();

            $table->index(['allocatable_type', 'allocatable_id'], 'vpa_allocatable_idx');
            $table->index('vendor_payment_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_payment_allocations');
        Schema::dropIfExists('vendor_payments');
    }
};
