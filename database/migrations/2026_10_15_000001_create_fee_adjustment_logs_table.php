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
        Schema::create('fee_adjustment_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fee_invoice_id')->nullable()->constrained('fee_invoices')->nullOnDelete();
            $table->foreignId('student_id')->nullable()->constrained('students')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('invoice_no', 50)->nullable();
            $table->string('student_identity', 50)->nullable();
            $table->enum('mode', ['increase', 'decrease', 'set_amount']);
            $table->enum('scope', ['single', 'continuous']);
            $table->decimal('adjustment_amount', 10, 2);
            $table->decimal('previous_amount', 10, 2);
            $table->decimal('previous_discount', 10, 2);
            $table->decimal('previous_net_amount', 10, 2);
            $table->decimal('previous_due_amount', 10, 2);
            $table->decimal('new_amount', 10, 2);
            $table->decimal('new_discount', 10, 2);
            $table->decimal('new_net_amount', 10, 2);
            $table->decimal('new_due_amount', 10, 2);
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->string('reason', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fee_adjustment_logs');
    }
};
