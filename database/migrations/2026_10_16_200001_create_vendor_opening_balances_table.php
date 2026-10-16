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
        Schema::create('vendor_opening_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('expense_vendors')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->decimal('opening_balance', 12, 2);
            $table->decimal('paid_amount', 12, 2)->default(0.00);
            $table->date('effective_date');
            $table->string('reference_no', 100)->nullable();
            $table->date('reference_date')->nullable();
            $table->string('status', 20)->default('Unpaid'); // 'Unpaid', 'Partially Paid', 'Paid'
            $table->text('note')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('vendor_id');
            $table->index('branch_id');
            $table->index('effective_date');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_opening_balances');
    }
};
