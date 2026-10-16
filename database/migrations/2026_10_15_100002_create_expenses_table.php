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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('voucher_no', 50)->unique();
            $table->foreignId('expense_category_id')->constrained('expense_categories')->restrictOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->date('expense_date');
            $table->decimal('amount', 10, 2);
            $table->string('payment_method', 50)->default('Cash');
            $table->string('paid_to', 150);
            $table->string('reference_no', 100)->nullable();
            $table->string('receipt_file', 255)->nullable();
            $table->text('note')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('expense_date');
            $table->index('expense_category_id');
            $table->index('branch_id');
            $table->index('payment_method');
            $table->index('paid_to');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
