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
        Schema::create('fee_payments', function (Blueprint $table) {
        $table->id();
        $table->string('receipt_no')->unique(); 
        $table->foreignId('fee_invoice_id')->constrained()->cascadeOnDelete();
        $table->foreignId('student_id')->constrained()->cascadeOnDelete();
        $table->decimal('paid_amount', 10, 2); 
        $table->date('payment_date');
        $table->string('payment_method')->default('Cash'); 
        $table->string('transaction_id')->nullable(); 
        $table->text('note')->nullable();
        $table->foreignId('collected_by')->constrained('users'); 
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fee_payments');
    }
};
